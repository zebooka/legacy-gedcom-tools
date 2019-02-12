
all: hooks composer test install try

hooks:
	test ! -d .git || cp .git-pre-commit .git/hooks/pre-commit && chmod +x .git/hooks/pre-commit

composer:
	composer -v install --no-dev && \
	COMPOSER_VENDOR_DIR="vendor-dev" composer -v install

test:
	./tests/run.sh

install:
	./build-phar.php

try:
	./build/gedcom-tools.phar
