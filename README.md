# wp-plugin-framework-routing
*Routing package for cekw/wp-plugin-framework-core*
## Developer notes

### Install dependencies
Using composer to install plugin dependencies:
`composer install`

### Test and code-coverage
There are several scripts to helps You testing Your code or check code-style:

|Scriptname|Example|Description|
|---|---|---|
|build:cc               | composer run build:cc                 |Generates the coverage files|
|build:cs&#x2011;diff   | composer run build:cs&#x2011;diff     |Generates a file that contains difference between Your and a fixed version of code|
|check:cs               | composer run check:cs                 |Check code-style|
|fix:cs                 | composer run fix:cs                   |Fix code-style|
|patch:cs&#x2011;diff   | composer run patch:cs&#x2011;diff     |Using generated .diff-file to fix code-style|
|start&#x2011;server:cc | composer run start&#x2011;server:cc   |Run local webserver to display generated coverage files|
|test                   | composer run test                     |Run unit-tests|