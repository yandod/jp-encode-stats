install:
	docker compose run composer composer install
run:
	docker compose run php-cli php ./src/run.php