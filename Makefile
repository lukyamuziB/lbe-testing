TAG = $(shell git rev-parse --short HEAD)
PROJECT = sandbox-kube
IMAGE = us.gcr.io/$(PROJECT)/lenken-server
pwd = $(shell pwd)

all: test

dependencies:
	composer install --prefer-source --no-interaction

test:
	vendor/bin/phpspec run

image:
	docker build -t $(IMAGE):$(TAG) -t $(IMAGE):latest .

push: image
	gcloud docker push $(IMAGE):$(TAG)
	gcloud docker push $(IMAGE):latest

deploy: push
	kubectl set deployment/lenken-server lenken-server=$(IMAGE):$(TAG)

deploy_stag:
	make deploy project=microservices-kube

deploy_prod:
	make deploy project=andela-kube

minikube:
	git submodule update --init
	eval $$(minikube docker-env) && docker build -t $(IMAGE):latest .

run:
	docker run $(IMAGE):$(TAG)


