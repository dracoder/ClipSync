import dotenv from 'dotenv';
dotenv.config();
import os from 'os';

const config = {
    listenIp: '0.0.0.0',
    listenPort: 3000,
    sslCrt: process.env.SSL_CERT_PATH,
    sslKey: process.env.SSL_KEY_PATH,
    mediasoup: {
        numWorkers: Object.keys(os.cpus()).length,
        // Worker settings
        worker: {
            rtcMinPort: 10000,
            rtcMaxPort: 10100,
            logLevel: 'debug',
            logTags: [
                'info',
                'ice',
                'dtls',
                'rtp',
                'srtp',
                'rtcp',
                'rtx',
                'bwe',
                // 'score',
                'simulcast',
                // 'svc'
            ],
        },
        // Router settings
        router: {
            mediaCodecs: [
                {
                    kind: 'audio',
                    mimeType: 'audio/opus',
                    clockRate: 48000,
                    channels: 2,
                },
                {
                    kind: 'video',
                    mimeType: 'video/VP8',
                    clockRate: 90000,
                    parameters: {
                        'x-google-start-bitrate': 1000,
                    },
                },
                /* {
                    kind: 'video',
                    mimeType: 'video/H264',
                    clockRate: 90000,
                    parameters: {
                        'packetization-mode': 1,
                        'profile-level-id': '4d0032',
                        'level-asymmetry-allowed': 1,
                        'x-google-start-bitrate': 1000,
                    },
                } */
            ],
        },
        // WebRtcTransport settings
        webRtcTransport: {
            listenIps: [
                {
                    ip: process.env.WEBRTC_LISTEN_IP || '0.0.0.0',
                    announcedIp: process.env.PUBLIC_IP || null,
                },
            ],
            // maxIncomingBitrate: 2500000, // Increased to 2.5 Mbps
            // maxIncomingBitrate: 4000000, // Increased to 2.5 Mbps
            // initialAvailableOutgoingBitrate: 1500000, // Increased to 1.5 Mbps
            // initialAvailableOutgoingBitrate: 2500000, // Increased to 1.5 Mbps
        },
    },
};

export default config;
