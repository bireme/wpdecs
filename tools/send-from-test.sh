#!/bin/bash

dst="/home/moa/project/esferatur"
dir="/home/crisfoto/webapps/moacirmoda/tmp"

# bd
$ssh "mysqldump -u tmp -pmoa00moa esferatur > $dir/esferatur/esferatur.sql"
rsync -rv crisfoto:$dir/esferatur/esferatur.sql /tmp/esferatur.sql
$ssh "rm -rfv $dir/esferatur/esferatur.sql"

cd $dst/htdocs
wp db import /tmp/esferatur.sql
wp search-replace moacirmoda.com/tmp/esferatur esferatur.dev
rm /tmp/esferatur.sql

rsync -rav $dir/esferatur/wp-content/uploads/* $dst/htdocs/wp-content/uploads/
