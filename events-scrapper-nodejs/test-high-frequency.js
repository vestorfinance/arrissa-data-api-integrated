/**
 * Test High-Frequency Scraping - Isolated Test
 */

const puppeteer = require('puppeteer');

async function testHighFrequencyScraping() {
  console.log('═'.repeat(60));
  console.log('  Testing High-Frequency Scraping');
  console.log('═'.repeat(60));
  console.log();

  let browser = null;
  let page = null;

  try {
    console.log('🚀 Step 1: Launching browser...');
    browser = await puppeteer.launch({ 
      headless: true,
      args: [
        '--no-sandbox', 
        '--disable-setuid-sandbox', 
        '--disable-dev-shm-usage',
        '--disable-blink-features=AutomationControlled',
        '--disable-features=IsolateOrigins,site-per-process'
      ]
    });
    console.log('✅ Browser launched successfully');

    console.log('🚀 Step 2: Creating new page...');
    page = await browser.newPage();
    
    // Set more realistic browser fingerprint
    await page.evaluateOnNewDocument(() => {
      Object.defineProperty(navigator, 'webdriver', { get: () => false });
      Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
      Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
      window.chrome = { runtime: {} };
    });
    
    await page.setViewport({ width: 1920, height: 1080 });
    console.log('✅ Page created with viewport 1920x1080');

    console.log('🚀 Step 3: Setting up request interception...');
    await page.setRequestInterception(true);
    
    let requestCount = 0;
    page.on('request', req => {
      requestCount++;
      if (['image', 'stylesheet', 'font', 'media'].includes(req.resourceType())) {
        req.abort();
      } else {
        req.continue({
          headers: {
            ...req.headers(),
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.9',
            'Accept-Encoding': 'gzip, deflate, br',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none',
            'Sec-Ch-Ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'Sec-Ch-Ua-Mobile': '?0',
            'Sec-Ch-Ua-Platform': '"Windows"',
            'Cache-Control': 'max-age=0'
          }
        });
      }
    });
    console.log('✅ Request interception configured');

    console.log('🚀 Step 4: Navigating to economic calendar...');
    console.log('   URL: https://sslecal2.forexprostools.com/');
    console.log('   Timeout: 180 seconds');
    
    const startTime = Date.now();
    try {
      await page.goto('https://sslecal2.forexprostools.com/', { 
        waitUntil: 'domcontentloaded', 
        timeout: 180000
      });
      const loadTime = ((Date.now() - startTime) / 1000).toFixed(2);
      console.log(`✅ Page loaded in ${loadTime} seconds`);
      console.log(`   Requests processed: ${requestCount}`);
    } catch (navError) {
      const loadTime = ((Date.now() - startTime) / 1000).toFixed(2);
      console.log(`⚠️ Navigation timed out after ${loadTime} seconds, trying fallback...`);
      
      await page.goto('https://sslecal2.forexprostools.com/', { 
        waitUntil: 'networkidle2', 
        timeout: 180000 
      });
      console.log('✅ Fallback navigation successful');
    }

    console.log('🚀 Step 5: Waiting for events table...');
    await page.waitForSelector('#ecEventsTable', { timeout: 120000 });
    console.log('✅ Events table found!');

    console.log('🚀 Step 6: Waiting for events to load (10 seconds)...');
    await new Promise(resolve => setTimeout(resolve, 10000));
    console.log('✅ Wait complete');

    console.log('🚀 Step 7: Extracting sample data...');
    
    // First check what's in the table
    const tableInfo = await page.evaluate(() => {
      const table = document.querySelector('#ecEventsTable');
      const tbody = table ? table.querySelector('tbody') : null;
      const allRows = tbody ? tbody.querySelectorAll('tr') : [];
      const eventRows = tbody ? tbody.querySelectorAll('tr.js-event-item') : [];
      
      // Get first 5 rows' classes
      const rowClasses = [];
      for (let i = 0; i < Math.min(5, allRows.length); i++) {
        rowClasses.push({
          index: i,
          className: allRows[i].className,
          id: allRows[i].id,
          hasEventAttr: allRows[i].hasAttribute('event_attr_id'),
          innerTextSample: allRows[i].innerText.substring(0, 100)
        });
      }
      
      return {
        tableExists: !!table,
        tbodyExists: !!tbody,
        totalRows: allRows.length,
        eventRows: eventRows.length,
        rowClasses: rowClasses
      };
    });
    
    console.log(`   Table exists: ${tableInfo.tableExists}`);
    console.log(`   Tbody exists: ${tableInfo.tbodyExists}`);
    console.log(`   Total rows: ${tableInfo.totalRows}`);
    console.log(`   Event rows (.js-event-item): ${tableInfo.eventRows}`);
    console.log('\n   First 5 rows structure:');
    tableInfo.rowClasses.forEach(row => {
      console.log(`   Row ${row.index}:`);
      console.log(`     Class: "${row.className}"`);
      console.log(`     ID: "${row.id}"`);
      console.log(`     Has event_attr_id: ${row.hasEventAttr}`);
      console.log(`     Text: ${row.innerTextSample}`);
      console.log();
    });
    
    const eventCount = tableInfo.eventRows;
    console.log(`✅ Found ${eventCount} events on the page`);

    // Extract first 3 events as sample
    const sampleEvents = await page.evaluate(() => {
      const rows = document.querySelectorAll('#ecEventsTable tbody tr.js-event-item');
      const events = [];
      
      for (let i = 0; i < Math.min(3, rows.length); i++) {
        const row = rows[i];
        const timeEl = row.querySelector('.time');
        const eventEl = row.querySelector('.event a');
        const currencyEl = row.querySelector('.flagCur .ceFlags');
        const forecastEl = row.querySelector('.fore');
        
        events.push({
          time: timeEl ? timeEl.innerText.trim() : '',
          event: eventEl ? eventEl.innerText.trim() : '',
          currency: currencyEl ? currencyEl.getAttribute('title') : '',
          forecast: forecastEl ? forecastEl.innerText.trim() : ''
        });
      }
      
      return events;
    });

    console.log('\n📊 Sample Events:');
    console.log('─'.repeat(60));
    sampleEvents.forEach((event, index) => {
      console.log(`${index + 1}. ${event.event}`);
      console.log(`   Time: ${event.time} | Currency: ${event.currency}`);
      console.log(`   Forecast: ${event.forecast || 'N/A'}`);
      console.log();
    });

    console.log('═'.repeat(60));
    console.log('✅ HIGH-FREQUENCY SCRAPING TEST PASSED!');
    console.log('═'.repeat(60));

  } catch (error) {
    console.error('\n❌ TEST FAILED!');
    console.error('═'.repeat(60));
    console.error('Error:', error.message);
    console.error('Stack:', error.stack);
    console.error('═'.repeat(60));
  } finally {
    if (browser) {
      console.log('\n🔒 Closing browser...');
      await browser.close();
      console.log('✅ Browser closed');
    }
  }
}

// Run the test
testHighFrequencyScraping().catch(console.error);
