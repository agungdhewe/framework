#!/bin/bash

# export PS1='\[\033[32m\]\u@\h\[\033[0m\]:\[\033[34m\]\W\[\033[0m\]\$ '


# URL="https://webservice.localhost/template/assets/sampleimage.svg"
# URL="http://localhost:8005/testhalaman"
# URL="https://webservice.localhost"
URL="https://fgta5.localhost?cleardebug=1"

echo GET $URL
curl -D - $URL