#!/bin/sh
# This command retrieves the strings that need to be translated
sgrep -o "%r\n" '"getMLText(\"" __ "\""' */*.php|sort|uniq -c
