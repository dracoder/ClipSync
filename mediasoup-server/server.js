import mediasoup from "mediasoup";
import fs from "fs";
import https from "https";
import http from "http";
import express from "express";
import { Server } from "socket.io";
import config from "./config.js";

const rooms = new Map();
// Global variables
let worker;
let webServer;
let socketServer;
let expressApp;
let mediasoupRouter;

(async () => {
    try {
        await runExpressApp();
        await runWebServer();
        await runSocketServer();
        await runMediasoupWorker();
    } catch (err) {
        console.error(err);
    }
})();

async function runExpressApp() {
    expressApp = express();
    expressApp.use(express.json());
    // expressApp.use(express.static(__dirname));

    expressApp.use((error, req, res, next) => {
        if (error) {
            console.warn("Express app error,", error.message);

            error.status =
                error.status || (error.name === "TypeError" ? 400 : 500);

            res.statusMessage = error.message;
            res.status(error.status).send(String(error));
        } else {
            next();
        }
    });
}

async function runWebServer() {
    const { sslKey, sslCrt } = config;
    let protocol = "http";
    if (!fs.existsSync(sslKey) || !fs.existsSync(sslCrt)) {
        webServer = http.createServer(expressApp);
        protocol = "http";
        // console.error('SSL files are not found. check your config.js file');
        // process.exit(0);
    } else {
        webServer = https.createServer(
            {
                key: fs.readFileSync(sslKey),
                cert: fs.readFileSync(sslCrt),
            },
            expressApp
        );
        protocol = "https";
    }
    webServer.on("error", (err) => {
        console.error("starting web server failed:", err.message);
    });

    await new Promise((resolve) => {
        const { listenIp, listenPort } = config;
        webServer.listen(listenPort, listenIp, () => {
            const listenIps = config.mediasoup.webRtcTransport.listenIps[0];
            const ip = listenIps.announcedIp || listenIps.ip;
            console.log("server is running");
            console.log(
                `open ${protocol}://${ip}:${listenPort} in your web browser`
            );
            resolve();
        });
    });
}

async function runSocketServer() {
    socketServer = new Server(webServer, {
        serveClient: false,
        path: "/server",
        log: false,
    });

    socketServer.on("connection", (socket) => {
        let producers = [];
        socket.on("join", (data) => {
            socket.room = data.room;
            socket.user = data.user;
            socket.is_owner = data.user.is_owner;
            if (data.user.is_owner) {
                socket.join(socket.room + "_admin");
            }
            socket.join(socket.room);
            socketServer.to(socket.id).emit("joined", {
                id: socket.id,
            });
        });

        socket.on("join_request", (data) => {
            socket.user = data.user;
            socket.room = data.room;
            socket.is_waiting = true;
            socketServer.to(socket.room + "_admin").emit("join_request", {
                id: socket.id,
                user: data.user,
                socket_id: socket.id,
            });
            socket.join(socket.room + "_waiting");
            socketServer.to(socket.id).emit("joined", {
                id: socket.id,
            });
        });

        socket.on("admit_accepted", (data) => {
            // send the list of all the users in the room except the user who is requesting
            // let users = rooms.get(socket.room).filter((user) => user.id !== data.socket_id);
            socketServer.to(data.socket_id).emit("admit_accepted", {
                user: data.user,
                room: data.room,
            });
            // make participant join the room
            let participantSocket = socketServer.sockets.sockets.get(
                data.socket_id
            );
            if (participantSocket) {
                participantSocket.join(data.room);
                participantSocket.leave(data.room + "_waiting");
            }
            // remove the user from the waiting list
        });
        socket.on("admit_rejected", (data) => {
            socketServer.to(data.socket_id).emit("admit_rejected", {
                user: data.user,
                room: data.room,
            });
            // remove the user from the waiting list
            let participantSocket = socketServer.sockets.sockets.get(
                data.socket_id
            );
            if (participantSocket){
                participantSocket.leave(data.room + "_waiting");
            }
        });

        if (producers.length) {
            socket.on(socket.room).emit("producers", producers);
        }

        socket.on("disconnect", () => {
            if (socket.producerTransport) {
                socket
                    .to(socket.room)
                    .emit("producer_disconnect", { user_id: socket.user_id });
                socket.producerTransport.close();
            }
        });

        socket.on("close_transport", () => {
            socket
                .to(socket.room)
                .emit("producer_disconnect", { user_id: socket.user_id });
            socket.producerTransport.close();
        });

        socket.on("connect_error", (err) => {
            console.error("client connection error", err);
        });

        socket.on("getRouterRtpCapabilities", (data, callback) => {
            callback(mediasoupRouter.rtpCapabilities);
        });

        socket.on("createProducerTransport", async (data, callback) => {
            try {
                const { transport, params } = await createWebRtcTransport();
                socket.producerTransport = transport;
                callback(params);
            } catch (err) {
                console.error(err);
                callback({ error: err.message });
            }
        });

        socket.on("createConsumerTransport", async (data, callback) => {
            try {
                const { transport, params } = await createWebRtcTransport();
                socket.consumerTransport = transport;
                callback(params);
            } catch (err) {
                console.error(err);
                callback({ error: err.message });
            }
        });

        socket.on("connectProducerTransport", async (data, callback) => {
            await socket.producerTransport.connect({
                dtlsParameters: data.dtlsParameters,
            });
            callback();
        });

        socket.on("connectConsumerTransport", async (data, callback) => {
            await socket.consumerTransport.connect({
                dtlsParameters: data.dtlsParameters,
            });
            callback();
        });

        socket.on("produce", async (data, callback) => {
            const { kind, rtpParameters } = data;
            let producer = await socket.producerTransport.produce({
                kind,
                rtpParameters,
            });
            callback({ id: producer.id });
        });

        socket.on("presentation_request", async (data, callback) => {
            socket.to(socket.room + "_admin").emit("presentation_request", {
                user: data.user,
                socket_id: socket.id,
            });
            socketServer.to(socket.id).emit("presentation_requesting");
            callback();
        });

        socket.on("presentation_response", async (data, callback) => {
            if (data.allow) {
                const sockets = await socketServer
                    .in(data.socket_id)
                    .fetchSockets();
                for (const socket of sockets) {
                    socket.is_allowed = true;
                }
                socketServer.to(data.socket_id).emit("presentation_access", {
                    allowed: true,
                });
            } else {
                socketServer.to(data.socket_id).emit("presentation_access", {
                    allowed: false,
                });
            }
            callback();
        });

        socket.on(
            "producer_joined",
            async ({ id, user, producer, screen }, callback) => {
                socket.to(socket.room).emit("new_producer", {
                    id,
                    producer,
                    user,
                    screen,
                });
                socket.producer = producer;
                socket.user = user;
                callback(producer);
            }
        );

        socket.on("producer_left", async (data, callback) => {
            socket.to(socket.room).emit("producer_left", {
                producer: data.producer,
                user: data.user,
                screen: data.screen,
                kickout : data.kickout
            });
            callback(producers);
        });

        socket.on("consume", async (data, callback) => {
            let consumer = {
                video: null,
                audio: null,
                screen: null,
            };
            if (data.producer.video) {
                consumer.video = await createConsumer(
                    socket.consumerTransport,
                    data.producer.video,
                    data.rtpCapabilities
                );
            }
            if (data.producer.audio) {
                consumer.audio = await createConsumer(
                    socket.consumerTransport,
                    data.producer.audio,
                    data.rtpCapabilities
                );
            }
            if (data.producer.screen) {
                consumer.screen = await createConsumer(
                    socket.consumerTransport,
                    data.producer.screen,
                    data.rtpCapabilities
                );
            }
            callback(consumer);
        });

        socket.on("waiting_list", async (data, callback) => {
            let waiting_users = [];
            let clients = await socketServer.in(socket.room + "_waiting").fetchSockets();
            if (clients !== undefined) {
                for (let i = 0; i < clients.length; i++) {
                    let client = clients[i];
                    waiting_users.push({
                        id: client.id,
                        user: client.user,
                    });
                }
            }
            callback(waiting_users);
        });

        socket.on("producer_list", async (data, callback) => {
            const producers = [];
            let clients = await socketServer.in(socket.room).fetchSockets();
            if (clients !== undefined) {
                for (let i = 0; i < clients.length; i++) {
                    let client = clients[i];
                    if (client.id != socket.id && client.producer) {
                        producers.push({
                            id: client.id,
                            producer: client.producer,
                            user: client.user,
                        });
                    }
                }
            }
            callback(producers);
        });

        socket.on("resume", async (data, callback) => {
            console.log("resume called");
            // await consumer.resume();
            callback();
        });

        socket.on("mute", async (data, callback) => {
            socket.to(socket.room).emit("mute", {
                id: socket.id,
                type: data.type,
                value: data.value,
            });
            callback();
        });
        socket.on("chat_message", async (data, callback) => {
            socket.to(socket.room).emit("chat_message", data);
            callback();
        });

        socket.on("admin_restriction", async (data, callback) => {
            socketServer.to(data.mute_target).emit("admin_restriction", data);
             callback();
        });

        socket.on("admin_kickout", async (data, callback) => {
            socketServer.to(data.kick_target).emit('admin_kickout', data);
            callback();
        });
    });
}

async function runMediasoupWorker() {
    worker = await mediasoup.createWorker({
        logLevel: config.mediasoup.worker.logLevel,
        logTags: config.mediasoup.worker.logTags,
        rtcMinPort: config.mediasoup.worker.rtcMinPort,
        rtcMaxPort: config.mediasoup.worker.rtcMaxPort,
    });

    worker.on("died", () => {
        console.error(
            "mediasoup worker died, exiting in 2 seconds... [pid:%d]",
            worker.pid
        );
        setTimeout(() => process.exit(1), 2000);
    });

    const mediaCodecs = config.mediasoup.router.mediaCodecs;
    mediasoupRouter = await worker.createRouter({ mediaCodecs });
}

async function createWebRtcTransport() {
    // const { maxIncomingBitrate, initialAvailableOutgoingBitrate } =
    //     config.mediasoup.webRtcTransport;

    const transport = await mediasoupRouter.createWebRtcTransport({
        listenIps: config.mediasoup.webRtcTransport.listenIps,
        enableUdp: true,
        enableTcp: true,
        preferUdp: true,
        // initialAvailableOutgoingBitrate,
    });
    // if (maxIncomingBitrate) {
    //     try {
    //         await transport.setMaxIncomingBitrate(maxIncomingBitrate);
    //     } catch (error) {
    //         console.error("failed to set max incoming bitrate:", error);
    //     }
    // }
    return {
        transport,
        params: {
            id: transport.id,
            iceParameters: transport.iceParameters,
            iceCandidates: transport.iceCandidates,
            dtlsParameters: transport.dtlsParameters,
        },
    };
}

async function createConsumer(consumerTransport, producer, rtpCapabilities) {
    let consumer = null;
    let producerId = producer._id;
    if (
        !mediasoupRouter.canConsume({
            producerId: producerId,
            rtpCapabilities,
        })
    ) {
        console.error("can not consume");
        return;
    }
    try {
        consumer = await consumerTransport.consume({
            producerId: producerId,
            rtpCapabilities,
        });
    } catch (error) {
        console.error("consume failed", error);
        return;
    }

    if (consumer.type === "simulcast") {
        await consumer.setPreferredLayers({
            spatialLayer: 2,
            temporalLayer: 2,
        });
    }

    return {
        id: consumer.id,
        producerId: consumer.producerId,
        kind: consumer.kind,
        rtpParameters: consumer.rtpParameters,
        type: consumer.type,
        producer: producer,
    };
}
