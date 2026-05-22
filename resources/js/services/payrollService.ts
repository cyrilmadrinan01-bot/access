import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

export function post(routeName: string, data: any = {}) {
  return router.post(route(routeName), data, {
    preserveScroll: true,
  })
}

export function postWithId(routeName: string, id: number, data: any = {}) {
  return router.post(route(routeName, id), data, {
    preserveScroll: true,
  })
}

export function upload(routeName: string, formData: FormData) {
  return router.post(route(routeName), formData, {
    forceFormData: true,
    preserveScroll: true,
  })
}
