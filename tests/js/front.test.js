import { beforeEach, describe, expect, it, vi } from 'vitest'

const modulePath = '../../media/js/front.js'
const clearUrl = '?option=com_ajax&module=ishop_compare&method=clear&format=json'

/**
 * Создает Joomla API double с управляемым request().
 */
function createJoomlaApi(requestHandler = () => {}) {
  const calls = []

  return {
    calls,
    api: {
      getOptions: vi.fn((key) => (key === 'csrf.token' ? 'csrf-token-name' : null)),
      request: vi.fn((options) => {
        calls.push(options)
        requestHandler(options)
      }),
    },
  }
}

/**
 * Возвращает стандартную разметку одного экземпляра модуля.
 */
function renderModule(id = 'mod-ishop-compare-1', count = 3) {
  document.body.innerHTML = `
    <a id="${id}" class="mod_ishop_compare" data-ishop-compare aria-label="MOD_ISHOP_COMPARE_TEXT">
      <span class="text">MOD_ISHOP_COMPARE_TEXT</span>
      <small class="count">${count}</small>
    </a>
    <button class="btn" type="button" data-ishop-compare-clear data-ishop-compare-target="#${id}">
      MOD_ISHOP_COMPARE_CLEAR
    </button>
  `
}

describe('media/js/front.js', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
    vi.restoreAllMocks()
  })

  it('импортируется как ESM entrypoint без исключений', async () => {
    const mod = await import(modulePath)

    expect(mod.FRONT_ENTRY_MARKER).toBe('mod_ishop_compare.front')
    expect(typeof mod.initIshopCompare).toBe('function')
  })

  it('не выполняет сетевые side effects при импорте', async () => {
    const request = vi.fn()
    const fetchSpy = vi.fn()
    const xhrSpy = vi.fn()
    window.Joomla = { request }
    globalThis.fetch = fetchSpy
    globalThis.XMLHttpRequest = xhrSpy

    await import(`${modulePath}?network=${Date.now()}`)

    expect(request).not.toHaveBeenCalled()
    expect(fetchSpy).not.toHaveBeenCalled()
    expect(xhrSpy).not.toHaveBeenCalled()
  })

  it('не меняет DOM при импорте', async () => {
    document.body.innerHTML = '<main data-test="stable">Stable DOM</main>'
    const before = document.body.innerHTML

    await import(`${modulePath}?dom=${Date.now()}`)

    expect(document.body.innerHTML).toBe(before)
  })

  it('фиксирует текущий статус entrypoint без автоинициализации', async () => {
    const mod = await import(modulePath)

    expect(mod.FRONT_ENTRY_MARKER).toBe('mod_ishop_compare.front')
    expect(document.querySelectorAll('[data-ishop-compare]').length).toBe(0)
  })
})

describe('initIshopCompare()', () => {
  beforeEach(() => {
    document.body.innerHTML = ''
    vi.restoreAllMocks()
  })

  it('без модулей на странице не падает и возвращает пустой список bindings', async () => {
    const { initIshopCompare } = await import(modulePath)
    const { api } = createJoomlaApi()

    expect(initIshopCompare(document, api)).toEqual([])
  })

  it('навешивает обработчик на один модуль и не меняет посторонние узлы', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule()
    document.body.insertAdjacentHTML('beforeend', '<p id="outside">outside</p>')
    const outside = document.querySelector('#outside')
    const { api } = createJoomlaApi()

    const bindings = initIshopCompare(document, api)

    expect(bindings).toHaveLength(1)
    expect(bindings[0].module).toBe(document.querySelector('[data-ishop-compare]'))
    expect(bindings[0].clearButton).toBe(document.querySelector('[data-ishop-compare-clear]'))
    expect(document.querySelector('#outside')).toBe(outside)
  })

  it('для нескольких модулей clear-действие обновляет только связанный экземпляр', async () => {
    const { initIshopCompare } = await import(modulePath)
    document.body.innerHTML = `
      <a id="compare-a" data-ishop-compare aria-label="MOD_ISHOP_COMPARE_TEXT"><small class="count">3</small></a>
      <button data-ishop-compare-clear data-ishop-compare-target="#compare-a"></button>
      <a id="compare-b" data-ishop-compare aria-label="MOD_ISHOP_COMPARE_TEXT"><small class="count">7</small></a>
      <button data-ishop-compare-clear data-ishop-compare-target="#compare-b"></button>
    `
    const { api } = createJoomlaApi((options) => {
      options.onSuccess(JSON.stringify({ count: 0 }))
      options.onComplete()
    })

    initIshopCompare(document, api)
    document.querySelector('[data-ishop-compare-target="#compare-a"]').click()

    expect(document.querySelector('#compare-a .count').textContent).toBe('0')
    expect(document.querySelector('#compare-b .count').textContent).toBe('7')
  })

  it('click по кнопке очистки отправляет Joomla.request с ожидаемым URL, POST, headers и CSRF payload', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule()
    const { api, calls } = createJoomlaApi((options) => options.onComplete())

    initIshopCompare(document, api)
    document.querySelector('[data-ishop-compare-clear]').click()

    expect(calls).toHaveLength(1)
    expect(calls[0].url).toBe(clearUrl)
    expect(calls[0].method).toBe('POST')
    expect(calls[0].headers).toEqual({
      'Cache-Control': 'no-cache',
      'Content-Type': 'application/json',
    })
    expect(JSON.parse(calls[0].data)).toEqual({ action: 'clear', 'csrf-token-name': 1 })
  })

  it('отправляет payload без CSRF token, если Joomla.getOptions недоступен', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule()
    const calls = []
    const api = {
      request: vi.fn((options) => {
        calls.push(options)
        options.onComplete()
      }),
    }

    initIshopCompare(document, api)
    document.querySelector('[data-ishop-compare-clear]').click()

    expect(JSON.parse(calls[0].data)).toEqual({ action: 'clear' })
  })

  it('защищает от повторного click во время pending-запроса', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule()
    const { api, calls } = createJoomlaApi()
    const button = document.querySelector('[data-ishop-compare-clear]')

    initIshopCompare(document, api)
    button.click()
    button.click()

    expect(calls).toHaveLength(1)
    expect(button.disabled).toBe(true)
    expect(document.querySelector('[data-ishop-compare]').dataset.ishopComparePending).toBe('true')
  })

  it('successful response обновляет count и empty state', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule('mod-ishop-compare-success', 4)
    const { api } = createJoomlaApi((options) => {
      options.onSuccess(JSON.stringify({ data: { count: 0 } }))
      options.onComplete()
    })

    initIshopCompare(document, api)
    document.querySelector('[data-ishop-compare-clear]').click()

    expect(document.querySelector('.count').textContent).toBe('0')
    expect(document.querySelector('[data-ishop-compare]').dataset.ishopCompareEmpty).toBe('true')
  })

  it('error response не ломает DOM и снимает loading-state', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule('mod-ishop-compare-error', 5)
    const button = document.querySelector('[data-ishop-compare-clear]')
    const { api } = createJoomlaApi((options) => {
      options.onError()
      options.onComplete()
    })

    initIshopCompare(document, api)
    button.click()

    expect(document.querySelector('.count').textContent).toBe('5')
    expect(document.querySelector('[data-ishop-compare]').dataset.ishopCompareError).toBe('true')
    expect(button.disabled).toBe(false)
    expect(document.querySelector('[data-ishop-compare]').dataset.ishopComparePending).toBeUndefined()
  })

  it('malformed JSON не приводит к uncaught exception и сохраняет старое состояние', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule('mod-ishop-compare-malformed', 8)
    const { api } = createJoomlaApi((options) => {
      options.onSuccess('{broken json')
      options.onComplete()
    })

    initIshopCompare(document, api)

    expect(() => document.querySelector('[data-ishop-compare-clear]').click()).not.toThrow()
    expect(document.querySelector('.count').textContent).toBe('8')
  })

  it('после очистки кнопка доступна, а ссылка сохраняет доступное имя', async () => {
    const { initIshopCompare } = await import(modulePath)
    renderModule('mod-ishop-compare-a11y', 2)
    const button = document.querySelector('[data-ishop-compare-clear]')
    const link = document.querySelector('[data-ishop-compare]')
    const { api } = createJoomlaApi((options) => {
      options.onSuccess(JSON.stringify({ count: 0 }))
      options.onComplete()
    })

    initIshopCompare(document, api)
    button.click()

    expect(button.disabled).toBe(false)
    expect(link.getAttribute('aria-label')).toBe('MOD_ISHOP_COMPARE_TEXT')
  })
})
