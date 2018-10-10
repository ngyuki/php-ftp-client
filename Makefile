
all:
	docker-compose run --rm php composer install
	docker-compose run --rm php vendor/bin/phpunit --colors
