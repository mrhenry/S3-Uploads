docker buildx build --platform linux/amd64,linux/arm64 --push -f ./fake-gcs-server/Dockerfile -t ghcr.io/mrhenry/fake-gcs-server .
