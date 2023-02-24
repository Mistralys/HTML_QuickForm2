@echo off

set AnalysisLevel=5

cls 

echo -------------------------------------------------------
echo RUNNING PHPSTAN ANALYSIS @ LEVEL %AnalysisLevel%
echo -------------------------------------------------------

echo.

call ../vendor/bin/phpstan analyse -c ./config/phpstan.neon -l %AnalysisLevel% > phpstan/output.txt

start "" "phpstan/output.txt" 
