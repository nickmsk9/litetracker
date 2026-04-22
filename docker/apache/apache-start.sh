#!/bin/sh
set -eu

CERT_DIR="${LITETRACKER_TLS_CERT_DIR:-/etc/apache2/ssl}"
CERT_KEY="${CERT_DIR}/litetracker.key"
CERT_CRT="${CERT_DIR}/litetracker.crt"
TLS_HOSTS="${LITETRACKER_TLS_HOSTS:-localhost,bt.localhost}"

mkdir -p "${CERT_DIR}"

if [ ! -s "${CERT_KEY}" ] || [ ! -s "${CERT_CRT}" ]; then
	TMP_CONF="$(mktemp)"
	FIRST_HOST=""
	DNS_INDEX=1

	{
		echo "[req]"
		echo "default_bits = 2048"
		echo "prompt = no"
		echo "default_md = sha256"
		echo "x509_extensions = v3_req"
		echo "distinguished_name = dn"
		echo
		echo "[dn]"
		for host in $(printf '%s' "${TLS_HOSTS}" | tr ',' ' '); do
			host="$(printf '%s' "${host}" | xargs)"
			if [ -n "${host}" ]; then
				FIRST_HOST="${host}"
				break
			fi
		done
		if [ -z "${FIRST_HOST}" ]; then
			FIRST_HOST="localhost"
		fi
		echo "CN = ${FIRST_HOST}"
		echo "O = LiteTracker Local TLS"
		echo "OU = Development"
		echo
		echo "[v3_req]"
		echo "subjectAltName = @alt_names"
		echo "extendedKeyUsage = serverAuth"
		echo "keyUsage = digitalSignature, keyEncipherment"
		echo
		echo "[alt_names]"
		for host in $(printf '%s' "${TLS_HOSTS}" | tr ',' ' '); do
			host="$(printf '%s' "${host}" | xargs)"
			if [ -z "${host}" ]; then
				continue
			fi

			echo "DNS.${DNS_INDEX} = ${host}"
			DNS_INDEX=$((DNS_INDEX + 1))
		done
	} > "${TMP_CONF}"

	openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
		-keyout "${CERT_KEY}" \
		-out "${CERT_CRT}" \
		-config "${TMP_CONF}"

	rm -f "${TMP_CONF}"
fi

exec apache2-foreground
