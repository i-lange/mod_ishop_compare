/*
 * @package    mod_ishop_compare
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/mod_ishop_compare
 * @copyright  (C) 2025 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

export const FRONT_ENTRY_MARKER = 'mod_ishop_compare.front';

const COMPARE_SELECTOR = '[data-ishop-compare]';
const CLEAR_SELECTOR = '[data-ishop-compare-clear]';
const CLEAR_URL = '?option=com_ajax&module=ishop_compare&method=clear&format=json';

/**
 * Возвращает CSRF token Joomla, если он доступен в переданном API.
 */
function getCsrfToken(JoomlaApi) {
  if (!JoomlaApi || typeof JoomlaApi.getOptions !== 'function') {
    return null;
  }

  return JoomlaApi.getOptions('csrf.token') || null;
}

/**
 * Разбирает ответ Joomla.request без выбрасывания исключений наружу.
 */
function parseResponse(response) {
  try {
    if (typeof response === 'string') {
      return JSON.parse(response);
    }

    return response && typeof response === 'object' ? response : {};
  } catch {
    return {};
  }
}

/**
 * Достает count из нескольких возможных форматов JSON-ответа.
 */
function extractCount(responseData) {
  if (typeof responseData.count === 'number') {
    return responseData.count;
  }

  if (responseData.data && typeof responseData.data.count === 'number') {
    return responseData.data.count;
  }

  return null;
}

/**
 * Обновляет визуальное состояние одного экземпляра модуля сравнения.
 */
function updateModuleCount(moduleNode, count) {
  const normalizedCount = Math.max(0, Number.parseInt(String(count), 10) || 0);
  const countNode = moduleNode.querySelector('.count');

  if (countNode) {
    countNode.textContent = String(normalizedCount);
  }

  moduleNode.dataset.ishopCompareEmpty = normalizedCount === 0 ? 'true' : 'false';
}

/**
 * Инициализирует обработчики кнопок очистки без автоматического сетевого запроса.
 */
export function initIshopCompare(root = document, JoomlaApi = globalThis.window?.Joomla) {
  const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
  const ownerDocument = scope.ownerDocument || document;
  const modules = Array.from(scope.querySelectorAll(COMPARE_SELECTOR));

  if (scope.matches && scope.matches(COMPARE_SELECTOR)) {
    modules.unshift(scope);
  }

  return modules.map((moduleNode) => {
    const targetSelector = moduleNode.id ? `#${moduleNode.id}` : '';
    const buttonSelector = targetSelector
      ? `${CLEAR_SELECTOR}[data-ishop-compare-target="${targetSelector}"]`
      : CLEAR_SELECTOR;
    const clearButton = ownerDocument.querySelector(buttonSelector);
    let pending = false;

    /**
     * Отправляет запрос очистки и защищает кнопку от повторного клика.
     */
    const onClearClick = () => {
      if (pending || !JoomlaApi || typeof JoomlaApi.request !== 'function') {
        return;
      }

      const token = getCsrfToken(JoomlaApi);
      const payload = { action: 'clear' };

      if (token) {
        payload[token] = 1;
      }

      pending = true;
      clearButton.disabled = true;
      moduleNode.dataset.ishopComparePending = 'true';
      delete moduleNode.dataset.ishopCompareError;

      JoomlaApi.request({
        url: CLEAR_URL,
        method: 'POST',
        headers: {
          'Cache-Control': 'no-cache',
          'Content-Type': 'application/json',
        },
        data: JSON.stringify(payload),
        onSuccess(response) {
          const nextCount = extractCount(parseResponse(response));

          if (nextCount !== null) {
            updateModuleCount(moduleNode, nextCount);
          }
        },
        onError() {
          moduleNode.dataset.ishopCompareError = 'true';
        },
        onComplete() {
          pending = false;
          clearButton.disabled = false;
          delete moduleNode.dataset.ishopComparePending;
        },
      });
    };

    if (clearButton) {
      clearButton.addEventListener('click', onClearClick);
    }

    return {
      module: moduleNode,
      clearButton,
      onClearClick,
    };
  });
}

// Публикуем API для Joomla-шаблона без автоматической инициализации и DOM-мутаций.
globalThis.IshopCompare = {
  FRONT_ENTRY_MARKER,
  initIshopCompare,
};
