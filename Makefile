packages = utils

_build/containers/parallel-lint/id:
	docker build --iidfile _build/containers/parallel-lint/id --tag lucatume/parallel-lint:5.6 _build/containers/parallel-lint

.PHONY: $(packages)
$(packages): %:
	docker run --rm -v ${CURDIR}/packages/$@:/app lucatume/parallel-lint:5.6 \
		--colors \
		/app/src
	cd packages/$@ && phpunit
	vendor/bin/phpcbf --colors -p --standard=phpcs.xml -s ${CURDIR}/packages/$@/src ${CURDIR}/packages/$@/tests
	vendor/bin/phpcs --colors -p --standard=phpcs.xml -s ${CURDIR}/packages/$@/src
	docker run --rm -v ${CURDIR}:/app phpstan/phpstan analyse --autoload-file=/app/vendor/autoload.php /app/packages/$@/src

.PHONY: pre_commit
pre_commit: _build/containers/parallel-lint/id $(packages)
