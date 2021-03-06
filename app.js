const webpush = require('web-push');
const open = require('amqplib').connect('amqp://localhost');
const fs = require('fs');

const config = JSON.parse(fs.readFileSync('src/config.json', 'utf8'));

webpush.setVapidDetails(
    config.webPush.subject,
    config.webPush.publicKey,
    config.webPush.privateKey
);

const q = 'notification';

const options = {
    TTL: 172800 // 2 days
};

open.then(function (conn) {
    return conn.createChannel();
}).then(function (ch) {
    return ch.assertQueue(q).then(function (ok) {
        return ch.consume(q, function (msg) {
            if (msg !== null) {
                var messageBroker = JSON.parse(msg.content.toString());
                webpush.sendNotification(messageBroker.pushInfo, JSON.stringify(messageBroker.message), options);
                console.log(msg.content.toString());
                ch.ack(msg);
            }
        });
    }).catch(console.warn);
}).catch(console.warn);