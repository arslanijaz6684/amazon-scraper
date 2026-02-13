<!DOCTYPE html>
<html>
<head>
    <title>Amazon ASIN Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Amazon ASIN Scraper</h3>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <form action="{{ route('scrap') }}" method="POST" id="scrapeForm">
                        @csrf
                    </form>
                    <form action="{{ route('download') }}" method="POST" id="downloadForm">
                        @csrf
                    </form>
                        <form action="{{ route('download.new') }}" method="POST" id="downloadNewForm">
                        @csrf
                    </form>
                    <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <div class="mb-3">
                            <label for="file" class="form-label">Upload Excel File with ASINs</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                            <div class="form-text">
                                Upload an Excel file with ASIN numbers in the second column (Column B)
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="useProxy" name="use_proxy">
                                <label class="form-check-label" for="useProxy">
                                    Use Proxy (if configured)
                                </label>
                            </div>
                        </div>

                        <button type="submit" form="uploadForm" class="btn btn-primary">Upload ASINs</button>
                        <button type="submit" form="scrapeForm" class="btn btn-dark"
                                title="Scrap ASINs Manufacturer & Responsible Data">Process ASINs
                        </button>
                        <button type="submit" form="downloadNewForm" class="btn btn-success"
                                title="Download ASINs Manufacturer & Responsible Data in Excel">Download New ASINs
                        </button>
                        <button type="submit" form="downloadForm" class="btn btn-success"
                                title="Download ASINs Manufacturer & Responsible Data in Excel">Download All ASINs
                        </button>
                    </form>

                    <hr>

                    <h5>Instructions:</h5>
                    <ol>
                        <li>Prepare an Excel file with ASINs in Column B (starting from row 2)</li>
                        <li>Upload the file using the form above</li>
                        <li>The system will scrape manufacturer information from Amazon</li>
                        <li>Download the results in Excel format</li>
                    </ol>

                    <div class="alert alert-info">
                        <strong>Note:</strong> This process may take several minutes depending on the number of ASINs.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    setInterval(async () => {
        const res = await fetch(`/scrape-status`);
        const data = await res.json();
        console.log('Job status:', data.status);
        if(data.status === 'done') {
            console.log('Results:', data.status);
            clearInterval(this);
        }
    }, 5000);
</script>
</body>
</html>
