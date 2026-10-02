import { useQuery } from '@tanstack/react-query'

export default function App() {
  const { data, isError, isLoading } = useQuery({
    queryKey: ['ping'],
    queryFn: async () => (await fetch(`${import.meta.env.VITE_API_URL}/api/v1/ping`)).json(),
  })

  return (
    <main className="p-8">
      <h1 className="text-2xl font-semibold">Jewellity</h1>
      <p className="mt-2 text-sm text-neutral-600">
        API: {isLoading ? 'checking…' : isError ? 'unreachable' : data?.status}
      </p>
    </main>
  )
}
