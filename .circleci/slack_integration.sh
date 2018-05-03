#!/bin/bash
workflow=https://circleci.com/workflow-run/${CIRCLE_WORKFLOW_ID}
data=$(cat << EOF
    [ {
        "fallback": "Lenken Backend Build $CIRCLE_BUILD_NUM} has successfuly built on CircleCI.",
        "text": "*commit id*: <https://github.com/andela/lenken-server/commit/${CIRCLE_SHA1}|${CIRCLE_SHA1}>",
        "callback_id": "Deplo",
        "color": "#3AA3E3",
        "attachment_type": "default",
        "actions": [
        {
            "name": "deploy",
            "text": "Deploy to production",
            "style": "primary",
            "type": "button",
            "value": "yes",
            "url": "${workflow}"
        }
    ]
}
]
EOF
)

curl -X POST https://slack.com/api/chat.postMessage \
--data-urlencode token=${SLACK_TOKEN} \
--data-urlencode channel=${SLACK_CHANNEL} \
--data-urlencode username=${BOT_USER} \
--data-urlencode text="Lenken Backend Build *<${CIRCLE_BUILD_URL}|#${CIRCLE_BUILD_NUM}>* has successfuly built on CircleCI." \
-d attachments="${data}"
