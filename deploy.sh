#!/bin/bash
# Deploy The Covenant Blog to IONOS.
# Uploads everything in public/ except the live database, so deploys
# never overwrite posts, subscribers, or settings on the server.
set -e
rsync -avz --delete \
  --exclude 'data/blog.sqlite*' \
  --exclude 'logs' \
  --exclude '.ssh' \
  /Users/stevefebbraro/Pet/Git/CovenantBlog/public/ \
  a2367593@access-5020906592.webspace-host.com:./
echo "Deployed."
