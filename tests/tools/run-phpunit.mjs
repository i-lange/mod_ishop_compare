#!/usr/bin/env node

import { existsSync } from 'node:fs'
import { spawnSync } from 'node:child_process'

const phpCandidates = [
  process.env.PHP_BIN,
  'C:\\OSPanel\\modules\\PHP-8.3\\PHP\\php.exe',
  'php',
].filter(Boolean)

/**
 * Выбирает первый доступный PHP CLI для запуска PHPUnit.
 */
function resolvePhpBin() {
  for (const candidate of phpCandidates) {
    if (candidate === 'php' || existsSync(candidate)) {
      return candidate
    }
  }

  return 'php'
}

/**
 * Запускает PHPUnit через выбранный PHP, чтобы не зависеть от PATH Windows.
 */
function runPhpUnit() {
  const phpBin = resolvePhpBin()
  const phpunitBin = 'vendor/bin/phpunit'
  const args = [phpunitBin, ...process.argv.slice(2)]
  const env = { ...process.env }

  // Включаем режим покрытия Xdebug только для coverage-запусков PHPUnit.
  if (args.some((arg) => arg.startsWith('--coverage'))) {
    env.XDEBUG_MODE = env.XDEBUG_MODE || 'coverage'
  }

  const result = spawnSync(phpBin, args, {
    stdio: 'inherit',
    shell: process.platform === 'win32',
    env,
  })

  process.exit(result.status ?? 1)
}

runPhpUnit()
