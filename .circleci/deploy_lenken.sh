#!/bin/bash

configure_gcloud(){
  gcloud auth activate-service-account --key-file ${HOME}/auth_lenken_app.json
  gcloud --quiet config set project ${PROJECT_ID}
  gcloud --quiet config set compute/zone europe-west1-b
}

deploy_change(){
  gcloud compute project-info add-metadata --metadata backend_build_commit=${CIRCLE_SHA1}
  gcloud beta compute instance-groups managed rolling-action replace staging-lenken-api-group-manager --max-surge=3 --max-unavailable=0 --min-ready=200 --zone=europe-west1-b
}

main(){
  configure_gcloud
  deploy_change
}

main