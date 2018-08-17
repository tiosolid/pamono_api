# Example API Project with Symfony 3.4 #

This project presents a simple picture upload API. Picture must be sent in the request body as an base64 encoded string.

## Authorization ##
Users must authorize using the the `Bearer` authorization method, with a token.

## Available endpoints ##
* `/images`: Supports `GET` and `POST` methods.

## `config.yml` settings ##
* bearer_token: The authorization token;
* image_max_width: Max image width, in pixels;
* image_max_height: Max image height, in pixels;
* image_max_size: Max image size, in bytes;
