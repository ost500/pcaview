import os from 'os';
import puppeteer from 'puppeteer-core';

const email = 'ost500@me.com';
const password = 'Ostmylove500';

async function run() {
    const url = process.argv.slice(2)[0];

    // OS별 기본 Chrome 실행파일 경로
    let executablePath;
    if (os.platform() === 'darwin') {
        // MacOS
        executablePath = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
    } else if (os.platform() === 'win32') {
        // Windows
        executablePath = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
    } else {
        // Linux (예: Ubuntu)
        executablePath = '/usr/bin/google-chrome';
    }

    let browser;
    try {
        browser = await puppeteer.launch({
            headless: false, // 실제 브라우저 띄우고 싶으면 false
            executablePath,
            defaultViewport: null, // 로컬 환경 해상도 그대로
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-blink-features=AutomationControlled', // 자동화 탐지 방지
            ],
        });

        const page = await browser.newPage();
        await page.goto(url, { waitUntil: 'networkidle2' });

        await page.type('#loginId--1', email);
        await page.type('#password--2', password);
        await page.click('button.btn_g.highlight.submit');

        await page.waitForNavigation({ waitUntil: 'networkidle2' });

        await page.type('.KDC_Input__root__3M8Hf', 'https://pcaview.com');
        await page.click('button.KDC_Button__root__N26ep.KDC_Button__normal_medium__mu29P.KDC_Button__color_special__CUcY7.purge-btn')

        await page.waitForResponse(url);

        await page.evaluate(() => {
            document.querySelector('.KDC_Input__root__3M8Hf').value = '';
        });
        await page.type('.KDC_Input__root__3M8Hf', 'pcaview.com');
        await page.click('button.KDC_Button__root__N26ep.KDC_Button__normal_medium__mu29P.KDC_Button__color_special__CUcY7.purge-btn')

        await page.waitForResponse(url);

        await browser.close();
    } catch (error) {
        console.error(error);
        if (browser) await browser.close();
    }
}
run();
