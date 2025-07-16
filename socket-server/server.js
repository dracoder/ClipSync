import fs from "fs";
import https from "https";
import http from "http";
import express from "express";
import { Server } from "socket.io";
import config from "./config.js";

const rooms = new Map();
let webServer;
let socketServer;
let expressApp;

(async () => {
    try {
        await runExpressApp();
        await runWebServer();
        await runSocketServer();
    } catch (err) {
        console.error(err);
    }
})();

async function runExpressApp() {
    expressApp = express();
    expressApp.use(express.json());

    expressApp.use((error, req, res, next) => {
        if (error) {
            console.warn("Express app error,", error.message);
            error.status = error.status || (error.name === "TypeError" ? 400 : 500);
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
        const { listenIp } = config;
        const listenPort = process.env.VITE_SOCKET_FREE_PORT || 3001;
        webServer.listen(listenPort, listenIp, () => {
            const ip = process.env.SOCKET_IP || listenIp;
            console.log("server is running");
            console.log(`open ${protocol}://${ip}:${listenPort} in your web browser`);
            resolve();
        });
    });
}

async function runSocketServer() {
    socketServer = new Server(webServer, {
        serveClient: false,
        path: "/rtc-server",
        log: false,
    });

    socketServer.on("connection", (socket) => {
        socket.on("join", (data) => {
            socket.room = data.room;
            socket.user = data.user;
            socket.user.id = socket.id;
            socket.is_owner = data.user.is_owner;
            if (data.user.is_owner) {
                socket.join(socket.room + "_admin");
            }
            socket.join(socket.room);
            socketServer.to(socket.id).emit("joined", { id: socket.id });
        });

        socket.on("join_request", (data) => {
            socket.user = data.user;
            socket.user.id = socket.id;
            socket.room = data.room;
            socket.is_waiting = true;
            socketServer.to(socket.room + "_admin").emit("join_request", {
                id: socket.id,
                user: data.user,
                socket_id: socket.id,
            });
            socket.join(socket.room + "_waiting");
            socketServer.to(socket.id).emit("joined", { id: socket.id });
        });

        socket.on("admit_accepted", async (data) => {
            let participantSocket = socketServer.sockets.sockets.get(data.socket_id);
            if (participantSocket) {
                participantSocket.join(data.room);
                participantSocket.leave(data.room + "_waiting");
            }
            let clients = await socketServer.in(socket.room).fetchSockets();
            let users = clients.map((client) => client.user).filter((user) => user.id !== data.socket_id);
            socketServer.to(data.socket_id).emit("admit_accepted", { user: data.user, room: data.room , users: users});
        });

        socket.on("admit_rejected", (data) => {
            let participantSocket = socketServer.sockets.sockets.get(data.socket_id);
            if (participantSocket) {
                participantSocket.leave(data.room + "_waiting");
            }
            socketServer.to(data.socket_id).emit("admit_rejected", { user: data.user, room: data.room });
        });

        socket.on("offer", (data) => {
            socket.to(data.to).emit("offer", {
                offer: data.offer,
                user: socket.user,
                id: socket.id
            });
        });

        socket.on("answer", (data) => {
            socket.to(data.to).emit("answer", {
                answer: data.answer,
                user: socket.user,
                id: socket.id
            });
        });

        socket.on("ice-candidate", (data) => {
            socket.to(data.to).emit("ice-candidate", {
                candidate: data.candidate,
                user: socket.user,
                id: socket.id
            });
        });

        socket.on("participant_list", async (data, callback) => {
            const participant = [];
            let clients = await socketServer.in(data.room ? data.room : socket.room).fetchSockets();
            if (clients !== undefined) {
                for (let i = 0; i < clients.length; i++) {
                    let client = clients[i];
                    if (client.id != socket.id) {
                        participant.push(client.user);
                    }
                }
            }
            callback(participant);
        });

        socket.on("chat_message", async (data, callback) => {
            socket.to(socket.room).emit("chat_message", data);
            callback();
        });

        socket.on("mute", async (data, callback) => {
            socket.to(socket.room).emit("mute", {
                id: socket.id,
                type: data.type,
                value: data.value
            });
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

        socket.on("waiting_list", async (data, callback) => {
            let waiting_users = [];
            const roomName = data.room ? data.room : socket.room
            let clients = await socketServer.in(roomName + "_waiting").fetchSockets();

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

        socket.on("alert_full_studio", (data) => {
            socket.user = data.user;
            socket.user.id = socket.id;
            socket.room = data.room;
            socket.is_waiting = true;
            socketServer.to(socket.room + "_admin").emit("alert_full_studio", {
                id: socket.id,
                user: data.user,
                socket_id: socket.id,
            });
        });

        socket.on("share_screen", async (data, callback) => {
            socket.to(socket.room).emit("share_screen", {
                id: socket.id,
                user: socket.user,
                sharing: data.sharing
            });
            callback();
        });

        socket.on("disconnect", () => {
            socket.leave(socket.room);
            socket.to(socket.room).emit("participant_disconnected", { id: socket.id });
        });
    });
}
