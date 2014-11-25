# KON Makefile
# - compiles js and css source files

all:
	cli/build.php
clean:
	cli/build.php -c
help:
	cli/build.php --help
