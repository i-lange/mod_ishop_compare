#!/usr/bin/env node

import { readFileSync } from 'node:fs'

const cloverFile = 'build/coverage/php-clover.xml'
const minimumStatements = 55
const minimumMethods = 55

/**
 * Извлекает числовой XML-атрибут из корневой metrics-записи Clover.
 */
function readMetric(metrics, name) {
  const match = metrics.match(new RegExp(`${name}="(\\d+)"`))

  return match ? Number.parseInt(match[1], 10) : 0
}

/**
 * Проверяет базовый coverage gate без внешних XML-библиотек.
 */
function checkCoverage() {
  const xml = readFileSync(cloverFile, 'utf8')
  const metrics = xml.match(/<metrics\b[^>]*>/)?.[0] ?? ''
  const statements = readMetric(metrics, 'statements')
  const coveredStatements = readMetric(metrics, 'coveredstatements')
  const methods = readMetric(metrics, 'methods')
  const coveredMethods = readMetric(metrics, 'coveredmethods')
  const statementPercent = statements === 0 ? 100 : (coveredStatements / statements) * 100
  const methodPercent = methods === 0 ? 100 : (coveredMethods / methods) * 100

  if (statementPercent < minimumStatements || methodPercent < minimumMethods) {
    console.error(
      `PHP coverage ниже порога: statements ${statementPercent.toFixed(2)}%, methods ${methodPercent.toFixed(2)}%`,
    )
    process.exit(1)
  }

  console.log(
    `PHP coverage ok: statements ${statementPercent.toFixed(2)}%, methods ${methodPercent.toFixed(2)}%`,
  )
}

checkCoverage()
