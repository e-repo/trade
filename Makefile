UID=1000
DOCKER_ARGS=--log-level=ERROR

# be services
BE-POSTGRES=postgres
BE-NGINX=nginx
BE-FPM=php-fpm
BE-FPM-CONTAINER=t-php-fpm
BE-CLI=php-cli
BE-MAILER=mailer


# init apps
be-init: docker-down be-docker-pull be-docker-build be-docker-up be-post-install

# common command
down: docker-down
ps: docker-ps
#restart: down up
#test: t-test


# backend command
b-up: be-docker-up
b-shell:
	@docker compose run --rm $(BE-CLI) sh


be-docker-up:
	docker compose up -d -- $(BE-POSTGRES) $(BE-CLI) $(BE-FPM) $(BE-NGINX) $(BE-MAILER)

admin-docker-up:
	docker compose up -d -- $(TRAEFIK) $(ADMIN_NGINX) $(ADMIN_NODE)

docker-ps:
	@docker compose ps

docker-down:
	docker compose down --remove-orphans

#docker-down-clear:
#	docker compose down -v --remove-orphans

be-docker-pull:
	docker compose pull -- $(TRAEFIK) $(BE-POSTGRES) $(BE-FPM) $(BE-CLI) $(BE-NGINX)


be-docker-build:
	docker compose build -- $(TRAEFIK) $(BE-POSTGRES) $(BE-FPM) $(BE-CLI) $(BE-NGINX)

t-composer-install:
	@docker compose run --rm $(BE-CLI) composer install

t-wait-db:
	until docker compose exec -T $(BE-POSTGRES) pg_isready --timeout=0 --dbname=shop ; do sleep 1 ; done

be-post-install: t-composer-install t-wait-db t-migrations b-chown

t-migrations:
	@docker compose run --rm $(BE-CLI) php bin/console do:mi:mi --no-interaction

t-test:
	@docker compose run --rm $(BE-CLI) php bin/phpunit

b-chown:
	@docker exec $(BE-FPM-CONTAINER) chown -R $(UID):$(UID) ./
