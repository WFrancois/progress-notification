const open = require('amqplib').connect('amqp://localhost');
const fs = require('fs');
const {Client} = require('pg');
const http = require('request-promise-native');

const config = JSON.parse(fs.readFileSync('src/config.json', 'utf8'));

const q = 'streamlabs';

const requestSelectSub = 'SELECT * FROM streamlabs WHERE twitch_id = $1 LIMIT 1';
const requestUpdateSub = 'UPDATE streamlabs SET access_token = $1, refresh_token = $2 WHERE twitch_id = $3';

open.then(function (conn) {
    return conn.createChannel();
}).then(function (ch) {
    return ch.assertQueue(q).then(function (ok) {
        return ch.consume(q, function (msg) {
            if (msg !== null) {
                let message = JSON.parse(msg.content.toString());
                let sound = '';
                if(message.sound) {
                    sound = message.sound;
                }
                console.log(message);
                sendNotification(message.pushInfo, message.message, message.type, message.image, sound);
                ch.ack(msg);
            }
        });
    }).catch(console.warn);
}).catch(console.warn);

async function sendNotification(twitchId, message, type, image, sound) {
    const client = new Client({
        user: config.pdo.user,
        host: config.pdo.host,
        database: config.pdo.dbname,
        password: config.pdo.password,
        port: 5432
    });
    await client.connect();

    const query = await client.query(requestSelectSub, [twitchId]);

    if (!query.rows[0]) {
        return;
    }

    const streamlabSub = query.rows[0];

    let options = {
        method: 'POST',
        uri: 'https://streamlabs.com/api/v1.0/token',
        json: {
            grant_type: 'refresh_token',
            client_id: config.streamlabs.clientId,
            client_secret: config.streamlabs.clientSecret,
            refresh_token: streamlabSub.refresh_token,
        }
    };

    try {
        const refreshToken = await http(options);

        if(refreshToken.access_token && refreshToken.refresh_token) {
            await client.query(requestUpdateSub, [refreshToken.access_token, refreshToken.refresh_token, twitchId]);

            options = {
                method: 'POST',
                uri: 'https://streamlabs.com/api/v1.0/alerts',
                json: {
                    access_token: refreshToken.access_token,
                    type: type,
                    message: message,
                    image_href: image,
                    sound_href: sound
                }
            };

            await http(options);
        }
    } catch(error) {
        console.warn(error.error);
    }

    await client.end();
}