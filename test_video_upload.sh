#!/bin/sh

# This script should execute successfully if the auth works with key 'supersecret'.

# Create a temp file to upload:
echo "Hello" > /tmp/testfile.txt

timestamp=`date +%s`

file_path="myfile"
file_hash=`sha256sum /tmp/testfile.txt | cut -d " " -f 1`
sign=`python -c "import hashlib, hmac; msg = 'file_path=$file_path&file_hash=$file_hash&timestamp=$timestamp'; print hmac.new('supersecret', msg,
 hashlib.sha256).hexdigest()"`

echo "Server response: "
curl -s localhost/receive.php -F file_path=$file_path -F file_hash=$file_hash -F timestamp=$timestamp -F sign=$sign -F file=@/tmp/testfile.txt |
python -m json.tool


# Clean up
rm /tmp/testfile.txt
