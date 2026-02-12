# Amazon ASIN Manufacturer Scraper
## Technical Documentation

---

# 1. Project Overview

The Amazon ASIN Manufacturer Scraper is a Laravel-based application that automates the extraction of:

- Manufacturer information
- Responsible person information

from Amazon product pages using ASIN numbers.

The system:

1. Reads ASINs from an Excel file
2. Navigates to Amazon product pages
3. Extracts safety/manufacturer data
4. Stores results in the database
5. Exports structured data to Excel

---

# 2. Purpose of the Application

## Why It Was Built

Manually collecting manufacturer information from Amazon is:

- Time-consuming
- Error-prone
- Not scalable

This system automates the process to improve efficiency and accuracy.

## Benefits

- Reduces manual data entry
- Handles thousands of ASINs
- Ensures consistent data structure
- Stores results for future use
- Generates formatted Excel reports

---

# 3. Technology Stack

| Layer | Technology |
|--------|------------|
| Backend | Laravel |
| Database | MySQL |
| Scraping | Puppeteer (Node.js) |
| HTML Parsing | Cheerio |
| Excel Handling | maatwebsite/excel |

---

# 4. Project File Structure

```
app/
├── Exports/
│   └── AsinDataExport.php
├── Http/
│   └── Controllers/
│       └── FileController.php
├── Imports/
│   └── AsinImport.php
├── Models/
│   └── AsinsData.php
├── Services/
│   ├── AmazonScraperService.php
│   └── ExcelService.php

database/
└── migrations/
    └── 2026_02_11_130115_create_asins_data_table.php

resources/
└── views/
    └── index.blade.php

routes/
└── web.php

scripts/
└── scrape.js
```

# 5. File Responsibilities

## 5.1 app/Exports/AsinDataExport.php
- Formats data for Excel export
- Defines column headings
- Structures manufacturer and responsible data

## 5.2 app/Http/Controllers/FileController.php
Main application controller.

Handles:
- File upload
- Scraping process
- Download functionality
- Success/error messages

Core methods:
- `index()`
- `upload()`
- `scrap()`
- `download()`
- `downloadNew()`

## 5.3 app/Imports/AsinImport.php
- Reads Excel file
- Extracts ASIN column
- Validates input

## 5.4 app/Models/AsinsData.php
Eloquent model for:

```
asins_data
```

Handles database interactions.

## 5.5 app/Services/AmazonScraperService.php
Core scraping service.

Responsibilities:
- Process ASIN list
- Call Node.js scraper
- Parse and return structured results

## 5.6 app/Services/ExcelService.php
- Handles Excel import
- Delegates to `AsinImport`
- Returns clean ASIN array

## 5.7 Migration File
Creates the `asins_data` table.

## 5.8 resources/views/index.blade.php
Frontend interface with:
- Upload form
- Process button
- Download buttons
- Flash messages

## 5.9 routes/web.php
Defines application routes.

## 5.10 scripts/scrape.js
Node.js Puppeteer script responsible for:
- Opening Amazon product pages
- Navigating to safety section
- Extracting manufacturer and responsible information

---

# 6. Application Workflow

## Step 1 – Upload ASINs
User uploads Excel → System extracts ASINs → Database inserts unique ASINs.

## Step 2 – Process ASINs
System selects ASINs where:
```
manufacturer IS NULL
OR
responsible IS NULL
```

Scraper extracts data and updates database.

## Step 3 – Export Data

User can:

- Download only new ASINs
- Download all ASINs

---

# 7. Database Structure

## Table: asins_data

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| asin | string | Unique ASIN |
| manufacturer | JSON | Manufacturer data |
| responsible | JSON | Responsible person data |
| new | boolean | Export flag |
| created_at | timestamp | Created time |
| updated_at | timestamp | Updated time |

---

# 8. Routes

```php
Route::controller(FileController::class)->group(function () {
    Route::get('/','index')->name('index');
    Route::post('/upload','upload')->name('upload');
    Route::post('/scrap','scrap')->name('scrap');
    Route::post('/download','download')->name('download');
    Route::post('/download-new','downloadNew')->name('download.new');
});
```
# 9. Key Code Snippets Section
Use this section to paste important code blocks.
## 9.1 Backend Key Snippets
### Upload Logic (FileController)
```php
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        try {
            $filePath = $request->file('file')->store('uploads');
            $fullPath = Storage::disk('local')->path($filePath);
            $result = $this->excelService->importAsins($fullPath);
            $result = collect($result)->map(function ($item) {
                return ['asin' => $item];
            })->toArray();
            AsinsData::insertOrIgnore($result);
            Storage::disk('local')->delete($filePath);
            return back()->with(['success' => 'ASINs upload successfully']);
        } catch (\Throwable $e) {
            return back()->with(['error' => 'File Upload failed. Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine()]);
        }
    }
```

### Scrap Logic (FileController)
```php
    public function scrap()
    {
        try {
            $asins = AsinsData::where(function ($query) {
                $query->whereNull('manufacturer')->orWhereNull('responsible');
            })->selectRaw('asin as ASIN')->get()->toArray();
            $result = $this->scraperService->processAsins($asins);
            $result = collect($result)->map(function ($item) {
                if (count($item['manufacturer']) === 0) {
                    $item['manufacturer'] = $this->getEmptyData();
                }
                if (count($item['responsible']) === 0) {
                    $item['responsible'] = $this->getEmptyData();
                }
                $item['manufacturer'] = json_encode($item['manufacturer'] ?? []);
                $item['responsible'] = json_encode($item['responsible'] ?? []);
                return $item;
            })->toArray();
            AsinsData::upsert($result, ['asin'], ['manufacturer', 'responsible']);
            return back()->with(['success' => 'ASINs fetched successfully']);
        } catch (\Throwable $e) {
            return back()->with(['error' => 'ASINs Processing Failed. Error: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine()]);
        }
    }
```

### AmazonScraperService
```php
    public function processAsins(array $asins): array
    {
        if (!isset($asins[0]['ASIN'])) {
            $asins = collect($asins)->map(function ($asin) {
                return ['ASIN' => $asin];
            })->toArray();
        }
        // Data you want to send to Node.js
        $dataToSend = json_encode($asins);
        $scriptPath = base_path('scripts/scrape.js');

        // Method using shell_exec for simplicity:
        $command = "node \"$scriptPath\" 2>&1 " . addslashes($dataToSend);
        $output = shell_exec($command);
        return json_decode($output, true);
    }
```

### ExcelService
````php
    public function importAsins(string $filePath): array
    {
        $import = new AsinImport();
        Excel::import($import, $filePath);

        return $import->getAsins();
    }
````

### Migration Schema
````php
    public function up(): void
    {
        Schema::create('asins_data', function (Blueprint $table) {
            $table->id();
            $table->string('asin')->unique();
            $table->json('manufacturer')->nullable();
            $table->json('responsible')->nullable();
            $table->boolean('new')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asins_data');
    }
````

## 9.2 Frontend Key Snippets

```bladehtml
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
```

## 9.3 Scraper Script
```javascript
    import puppeteer from 'puppeteer';
    import axios from 'axios';
    import * as cheerio from 'cheerio';
    const asins = process.argv[2];
    let responsible;
    let responsibleSelector;
    let elements,data;
    let manufacturer;
    let manufacturerSelector
    let responsibleSection,manufacturerSection;
    
    async function scrapeASINs(dataList) {
        dataList = JSON.parse(dataList);
    
        const browser = await puppeteer.launch({ headless: true });
        const excelData = [];
    
        try {
            for (let i = 0; i < dataList.length; i++) {
                const { ASIN: asin } = dataList[i];
                if (!asin) {
                    console.error(`ASIN is missing for item ${i}. Skipping...`);
                    continue;
                }
    
                try {
    
                    const url = 'https://www.amazon.de/acp/buffet-disclaimers-card/buffet-disclaimers-card-6c27e42b-7f00-484a-83bf-19afce8e783c-1770323010164/getRspManufacturerContent?page-type=Detail&stamp=1770732980964';
    
                    const headers = {
                        'accept': 'text/html, application/json',
                        'accept-language': 'en-GB,en;q=0.9,be;q=0.8,ur;q=0.7',
                        'content-type': 'application/json',
                        'device-memory': '8',
                        'downlink': '4.25',
                        'dpr': '2',
                        'ect': '4g',
                        'priority': 'u=1, i',
                        'rtt': '250',
                        'sec-ch-device-memory': '8',
                        'sec-ch-dpr': '2',
                        'sec-ch-ua': '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
                        'sec-ch-ua-mobile': '?1',
                        'sec-ch-ua-platform': '"Android"',
                        'sec-ch-ua-platform-version': '"6.0"',
                        'sec-ch-viewport-width': '1145',
                        'sec-fetch-dest': 'empty',
                        'sec-fetch-mode': 'cors',
                        'sec-fetch-site': 'same-origin',
                        'viewport-width': '1145',
                        'x-amz-acp-params': 'tok=FBsk2BFo33RUH3sujiaU_dkdakUcEBnthvUxK3jaTj4;ts=1734623286395;rid=YPAQAPMK7HS057YPN4AD;d1=711;d2=0',
                        'x-amz-amabot-click-attributes': 'disable',
                        'x-requested-with': 'XMLHttpRequest',
                        'cookie': 'session-id=261-5758951-0539711; session-id-time=2082787201l; i18n-prefs=EUR; lc-acbde=en_GB; sp-cdn="L5Z9:PK"; ubid-acbde=261-5393323-8128104; session-token=RVuGuCOz7rQrxfHb0cosNpD+u0bC7roD/2RaAnDtCXh9SGiSIzUEOGPNsdMo2/H607FyEYsyMy+zh8u/i3tXuhqUwki7bkMx1KYf8OFrr2SJsalca8qxe10aZmm1dq7UEZS1hA2CdN9EWE2sQGmHnBWb84YWuoPtFhBCv5BZGpWM42S8PYSiGlorZaav0JYEgUqVWCpJZpB13sq6Guy8C9wIrEjHGn2EtYaCj8PQiyZpQTF7qHQub3QSq517SaSOk+j8adBQPOeCOakcSgveJjTU/9y6sOi00KHadgZG4/x7rs5jm+ItnQBK1JoS81IGX2nsX4gCLycCjInxx9FUXE17K9oU4wil',
                        'Referer': 'https://www.amazon.de/dp/B0BJ1Q3HWZ?th=1',
                        'Referrer-Policy': 'strict-origin-when-cross-origin'
                    };
                    let requestBody = { asin };
                    // Await the axios response
                    let response = await axios.post(url, requestBody, { headers });
                    // Parse the response with Cheerio
                    let $ = cheerio.load(response.data);
                    // Extract responsible person info
                    responsibleSection = $('div#buffet-sidesheet-rsp-content-container .a-box-inner');
    
                    if ($('div#buffet-sidesheet-rsp-content-container').text().toLowerCase().includes('not available')) {
                        responsible = {
                            'name': 'Not available',
                            'address': 'Not available',
                            'phone': 'Not available',
                            'email': 'Not available'
                        }
    
                    }else {
                        responsible = {};
                        for (let x = 0; x < responsibleSection.length; x++) {
                            responsible[x] = {};
                            responsible[x]['name'] = responsibleSection.eq(x).find('span.a-size-base.a-text-bold').text().trim()
                            responsibleSelector = responsibleSection.eq(x).find('ul');
                            for (let i = 0; i < responsibleSelector.length; i++) {
                                elements = $(responsibleSelector[i]);
                                responsible[x]['address'] = [];
                                for (let j = 0; j < elements.children().length; j++) {
                                    data = elements.children().eq(j).text().trim();
                                    if (detectData(data) === 'email') {
                                        responsible[x]['email'] = data
                                    } else if (detectData(data) === 'phone') {
                                        responsible[x]['phone'] = data
                                    } else {
                                        responsible[x]['address'].push(data)
                                    }
                                }
                                responsible[x]['address'] = responsible[x]['address'].join(' ,')
                            }
                        }
                    }
    
                    manufacturerSection = $('div#buffet-sidesheet-manufacturer-content-container .a-box-inner');
                    if ($('div#buffet-sidesheet-manufacturer-content-container').text().toLowerCase().includes('not available')) {
                        manufacturer = {
                            'name': 'Not available',
                            'address': 'Not available',
                            'phone': 'Not available',
                            'email': 'Not available'
                        }
                    }else{
                        manufacturer = {};
                        for (let x = 0; x < manufacturerSection.length; x++) {
                            manufacturer[x] = {};
                            manufacturer[x]['name'] = manufacturerSection.eq(x).find('h6').text().trim();
                            manufacturerSelector = manufacturerSection.eq(x).find('ul');
                            for (let i = 0; i < manufacturerSelector.length; i++) {
                                elements = $(manufacturerSelector[i]);
                                manufacturer[x]['address'] = [];
                                for (let j = 0; j < elements.children().length; j++) {
                                    data = elements.children().eq(j).text().trim();
                                    if (detectData(data) === 'email') {
                                        manufacturer[x]['email'] = data
                                    } else if (detectData(data) === 'phone') {
                                        manufacturer[x]['phone'] = data
                                    } else {
                                        manufacturer[x]['address'].push(data)
                                    }
                                }
                                manufacturer[x]['address'] = manufacturer[x]['address'].join(' ,')
                            }
                        }
                    }
                    // Add to Excel data
                    excelData.push({
                        asin: asin,
                        'manufacturer':manufacturer,
                        'responsible':responsible
                    });
                } catch (error) {
                    console.error(`Error processing ASIN: ${asin} - ${error.message}`);
                }
            }
            return excelData;
    
        } catch (error) {
            console.error(`An unexpected error occurred: ${error.message}`);
        } finally {
            await browser.close();
        }
    }
    
    function detectData(data) {
        const value = data.trim();
    
        // Email regex (reasonable, not insane)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
        // Phone regex (supports international +, spaces, dashes, parentheses)
        const phoneRegex = /^(\+?\d{1,3}[\s-]?)?(\(?\d{2,4}\)?[\s-]?)?\d{3,4}[\s-]?\d{4}$/;
    
        // Address heuristic (numbers + street words)
        const addressRegex = /\d+\s+([A-Za-z]+\s?)+(street|st|road|rd|avenue|ave|boulevard|blvd|lane|ln|drive|dr|court|ct)\b/i;
    
        if (emailRegex.test(value)) {
            return "email";
        }
    
        if (phoneRegex.test(value)) {
            return "phone";
        }
    
        if (addressRegex.test(value)) {
            return "address";
        }
    
        return "address";
    }
    // console.error(asins)
    scrapeASINs(asins).then(r => console.log(JSON.stringify(r)));

```
# 10. Key Features

- Batch processing
- Duplicate ASIN protection
- JSON structured storage
- Export tracking using new flag
- Error handling
- CAPTCHA detection
- Rate limiting
- Persistent database storage

# 11. Edge Cases Handled

- Empty Excel file
- Invalid ASIN
- Missing manufacturer information
- Amazon HTML changes
- CAPTCHA pages
- Network timeouts
- Large file processing
- Duplicate entries

# 12. Installation & Setup
```terminaloutput
    php artisan migrate
    php artisan storage:link
    composer require maatwebsite/excel
    npm install
    npm i puppeteer
    npm install cheerio
    php artisan serve
```
