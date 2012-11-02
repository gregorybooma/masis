.PHONY : all docs

all: docs

docs:
	doxygen docs/Doxyfile
	jsdoc resources/masis/*.js -d=docs/masis.js
