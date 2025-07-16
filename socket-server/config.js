import dotenv from "dotenv";
dotenv.config();

const config = {
    listenIp: "0.0.0.0",
    listenPort: 3001,
    sslCrt: process.env.SSL_CERT_PATH,
    sslKey: process.env.SSL_KEY_PATH,
};

export default config;
