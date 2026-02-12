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
