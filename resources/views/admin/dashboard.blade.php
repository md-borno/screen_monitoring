<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .screenshot-img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .screenshot-img:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .screenshot-info {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .no-screenshots {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .screenshot-container {
            position: relative;
            overflow: hidden;
        }

        .screenshot-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .screenshot-container:hover .screenshot-overlay {
            opacity: 1;
        }
    </style>
</head>

<body class="font-sans antialiased">
    @include('components.navbar')

    <div class="min-h-screen bg-gray-100">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold">Admin Dashboard - Screenshots Monitor</h2>
                            <div class="text-sm text-gray-500">
                                Total Screenshots: {{ $screenshots->total() }}
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                {{ session('error') }}
                            </div>
                        @endif

                        <!-- Debug Info (remove in production) -->
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <details>
                                <summary class="cursor-pointer font-medium text-blue-800">Debug Information</summary>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Storage Path: {{ storage_path('app/public') }}</p>
                                    <p>Public Path: {{ public_path('storage') }}</p>
                                    <p>First Screenshot Path:
                                        {{ $screenshots->first()->image_path ?? 'No screenshots' }}</p>
                                    <p>Full URL Example:
                                        {{ $screenshots->first() ? route('admin.screenshot.view', ['path' => $screenshots->first()->image_path]) : 'N/A' }}
                                    </p>
                                </div>
                            </details>
                        </div>

                        <!-- Filter Form -->
                        <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                            <h3 class="font-semibold text-lg mb-4">Filter Screenshots</h3>
                            <form action="{{ route('admin.filter') }}" method="GET" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                                            Employee
                                        </label>
                                        <select name="user_id" id="user_id"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <option value="">All Employees</option>
                                            @foreach ($employees as $employee)
                                                <option value="{{ $employee->id }}"
                                                    {{ request('user_id') == $employee->id ? 'selected' : '' }}>
                                                    {{ $employee->name }} ({{ $employee->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                                            Date
                                        </label>
                                        <input type="date" name="date" id="date"
                                            value="{{ request('date') }}"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    </div>

                                    <div class="flex items-end">
                                        <div class="space-x-2">
                                            <button type="submit"
                                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                                                Filter
                                            </button>
                                            <a href="{{ route('admin.dashboard') }}"
                                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                                Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="mt-4">
                                <form action="{{ route('admin.cleanup') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Are you sure you want to delete all screenshots older than 2 days?')"
                                        class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded text-sm">
                                        Delete Screenshots Older Than 2 Days
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Screenshots Grid -->
                        <div class="mb-4">
                            <p class="text-gray-600">
                                Showing {{ $screenshots->count() }} of {{ $screenshots->total() }} screenshots
                            </p>
                        </div>

                        @if ($screenshots->isEmpty())
                            <div class="no-screenshots">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <p class="text-lg text-gray-500 mb-2">No screenshots found</p>
                                <p class="text-sm text-gray-400">Start screen capture from employee dashboard to see
                                    screenshots here</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                                @foreach ($screenshots as $screenshot)
                                    <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow duration-300">
                                        <div class="mb-3">
                                            <!-- Try multiple image sources -->
                                            <img src="{{ route('admin.screenshot.view', ['path' => $screenshot->image_path]) }}"
                                                alt="Screenshot" class="screenshot-img"
                                                onerror="handleImageError(this, '{{ $screenshot->image_path }}')">
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <p><strong>Employee:</strong> {{ $screenshot->user->name }}</p>
                                            <p><strong>Date:</strong>
                                                {{ $screenshot->created_at->format('Y-m-d H:i:s') }}</p>
                                            <p><strong>Path:</strong> {{ $screenshot->image_path }}</p>
                                            <p><strong>File Exists:</strong> {{ $screenshot->file_exists ? '✅' : '❌' }}
                                            </p>
                                            <p class="text-xs">
                                                <a href="{{ route('admin.screenshot.view', ['path' => $screenshot->image_path]) }}"
                                                    target="_blank" class="text-blue-500 hover:underline">
                                                    Open in new tab
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <div class="mt-6">
                                {{ $screenshots->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for larger view -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
        <div class="relative max-w-4xl max-h-full">
            <button onclick="closeModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300 text-2xl">
                ✕
            </button>
            <img id="modalImage" src="" alt="" class="max-w-full max-h-screen rounded-lg">
        </div>
    </div>

   <script>
function handleImageError(img, path) {
    console.error('Failed to load image:', img.src);
    console.log('Image path:', path);

    // Try alternative URL
    const altUrl = '/storage/' + path;
    console.log('Trying alternative URL:', altUrl);

    img.src = altUrl;
    img.onerror = function() {
        // If still fails, show placeholder
        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgZmlsbD0iI2YzZjRmNiIvPjx0ZXh0IHg9IjIwMCIgeT0iMTUwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM5OTkiPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4=';
        this.style.border = '2px dashed #e53e3e';
    };
}
</script>
</body>

</html>
