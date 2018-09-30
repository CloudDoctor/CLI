all: prepare build push

prepare:
	composer install --ignore-platform-reqs

build:
	docker build -t gone/cloud-doctor:latest .

push:
	docker push gone/cloud-doctor:latest