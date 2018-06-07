#!/bin/bash
data=$(cat << EOF
    [ {
        "text": "*commit id*: <https://github.com/andela/lenken-server/commit/${CIRCLE_SHA1}|${commit}>",
        "callback_id": "Deplo",
        "color": "#3AA3E3",
        "attachment_type": "default",
}
]
EOF
)

curl -X POST https://slack.com/api/chat.postMessage \
--data-urlencode token=${SLACK_TOKEN} \
--data-urlencode channel=${SLACK_CHANNEL} \
--data-urlencode username=${BOT_USER} \
--data-urlencode text="Lenken Backend has been deployed to GCP ${deploy_env}." \
-d attachments="${data}"