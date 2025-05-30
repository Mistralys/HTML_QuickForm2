#!/usr/bin/env sh

MemoryLimit=900M
AnalysisLevel=6
OutputFile=./result.txt
ConfigFile=./config.neon
BinFolder=../../vendor/bin

echo "-------------------------------------------------------"
echo "RUNNING PHPSTAN @ LEVEL $AnalysisLevel"
echo "-------------------------------------------------------"
echo ""

$BinFolder/phpstan analyse -l $AnalysisLevel -c $ConfigFile --memory-limit=$MemoryLimit > $OutputFile
