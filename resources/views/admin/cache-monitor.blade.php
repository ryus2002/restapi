<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API 快取監控儀表板</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">API 快取監控儀表板</h1>
        
        @if (session('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">快取命中率</h5>
                        <h2 class="text-primary">{{ number_format($stats['hit_rate'], 1) }}%</h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">快取命中/未命中</h5>
                        <p>命中次數: {{ number_format($stats['hits']) }}</p>
                        <p>未命中次數: {{ number_format($stats['misses']) }}</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">快取資訊</h5>
                        <p>總快取項目數: {{ number_format($stats['total_keys']) }}</p>
                        <p>記憶體用量: {{ $stats['memory_usage'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mt-3">
            <form action="{{ route('admin.cache.clear') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">清除所有快取</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>