#!/usr/bin/env bash

USER=$(whoami)

if [ $USER != 'root' ]; then
    echo -e 'Necessário executar como sudo'
    exit
fi

echo "127.0.0.1       shoppingCart.local" >> /etc/hosts