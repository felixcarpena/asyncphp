COMPOSER=composer
CONTAINER=docker exec -w /app -itu docker asyncphp

### COMPOSER ###
.PHONY: composer
composer: disdebug
	$(CONTAINER) $(COMPOSER) $(cmd)

### EXECUTION HELPER ###
.PHONY: php
php: disdebug
	$(CONTAINER) php $(cmd)

### EXECUTION DEBUGING HELPER ###
.PHONY: dphp
dphp: endebug
	$(CONTAINER) bin/cli-debug.sh $(cmd)

### XDEBUG (en/dismod is not available)###
.PHONY: disdebug
disdebug:
	$(CONTAINER) sudo rm -rf /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

.PHONY: endebug
endebug:
	$(CONTAINER) sudo docker-php-ext-enable xdebug