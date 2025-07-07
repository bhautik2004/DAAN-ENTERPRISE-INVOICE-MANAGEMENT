<script>
document.getElementById('toggleSidebar').addEventListener('click', function() {
    let sidebar = document.getElementById('sidebar');
    let mainContent = document.getElementById('mainContent');
    let sidebarTexts = document.querySelectorAll('.sidebar-text');
    let sidebarLogo = document.querySelector('.sidebar-logo');

    if (sidebar.classList.contains('w-64')) {
        sidebar.classList.remove('w-64', 'p-5');
        sidebar.classList.add('w-16', 'p-2');
        mainContent.classList.remove('ml-64');
        mainContent.classList.add('ml-16');

        sidebarTexts.forEach(text => text.classList.add('hidden'));
        sidebarLogo.classList.add('hidden'); // Hide logo

        document.getElementById('toggleSidebar').style.right = '-60px'; // Adjust button position
    } else {
        sidebar.classList.remove('w-16', 'p-2');
        sidebar.classList.add('w-64', 'p-5');
        mainContent.classList.remove('ml-16');
        mainContent.classList.add('ml-64');

        sidebarTexts.forEach(text => text.classList.remove('hidden')); // Show text
        sidebarLogo.classList.remove('hidden'); // Show logo

        document.getElementById('toggleSidebar').style.right = '-45px'; // Reset button position
    }
});


async function checkPincode() {
    const pincode = document.getElementById('pincode').value;

    if (pincode) {
        try {
            const response = await fetch(`https://api.postalpincode.in/pincode/${pincode}`);
            const data = await response.json();

            if (data[0].Status === "Success") {
                alert('Valid Pincode');
            } else {
                alert('Invalid Pincode. Please enter a correct one.');
            }
        } catch (error) {
            alert('Error fetching pincode data. Please try again.');
        }
    } else {
        alert('Please enter a pincode.');
    }
}

function formatDate(dateString) {
    let date = new Date(dateString);

    let day = String(date.getDate()).padStart(2, '0');
    let month = String(date.getMonth() + 1).padStart(2, '0');
    let year = date.getFullYear();

    let hours = date.getHours();
    let minutes = String(date.getMinutes()).padStart(2, '0');

    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;

    return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
}


// For single invoice printing
function printInvoice(invoiceData, isMultiUp = false) {
    fetch('get_distributor.php')
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to fetch distributor');
            }
            return res.json();
        })
        .then(distributor => {
            invoiceData.distributor = distributor;
            const htmlContent = generatePrintPageHtml([invoiceData], isMultiUp);
            generatePdfFromHtml(htmlContent, `Invoice_${invoiceData.id}.pdf`, isMultiUp);
        })
        .catch(err => {
            console.error('Failed to fetch distributor:', err);
            alert('Failed to load distributor info. Please try again.');
        });
}

// For multiple invoice printing
function printSelectedInvoices(isMultiUp = false) {
    const selectedCheckboxes = document.querySelectorAll('input[name="selected[]"]:checked');

    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one invoice to print.');
        return;
    }

    const invoiceIds = Array.from(selectedCheckboxes).map(cb => cb.value);

    fetch('get_invoices.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                invoice_ids: invoiceIds
            })
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to fetch invoices');
            }
            return res.json();
        })
        .then(invoices => {
            fetch('get_distributor.php')
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to fetch distributor');
                    }
                    return res.json();
                })
                .then(distributor => {
                    invoices.forEach(invoice => {
                        invoice.distributor = distributor;
                    });
                    const htmlContent = generatePrintPageHtml(invoices, isMultiUp);
                    generatePdfFromHtml(htmlContent, `Invoices_${new Date().toISOString().slice(0,10)}.pdf`,
                        isMultiUp);
                })
                .catch(err => {
                    console.error('Failed to fetch distributor:', err);
                    alert('Failed to load distributor info. Please try again.');
                });
        })
        .catch(err => {
            console.error('Failed to fetch invoices:', err);
            alert('Failed to load selected invoices. Please try again.');
        });
}
// Common function to open print window
function openPrintWindow(htmlContent, isMultiUp = false) {
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    if (!printWindow) {
        alert('Popup window was blocked. Please allow popups for this site and try again.');
        return;
    }

    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();

    // For multi-up printing, we need to delay the print command slightly
    if (isMultiUp) {
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
                setTimeout(() => printWindow.close(), 100);
            }, 300);
        };
    }
}

// Common function to generate HTML for printing (single or multiple invoices)
function generatePrintPageHtml(invoices, isMultiUp = false) {
    // Generate CSS that will apply to all invoices
    const commonCSS = `
    <style>
        @page {
            size: ${isMultiUp ? 'A4' : '105mm 148mm'};
            margin: ${isMultiUp ? '5mm' : '2mm'};
        }
        body {
        -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: ${isMultiUp ? '10px' : '12px'};
        }
        ${isMultiUp ? `
        .multi-up-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 5mm;
            width: 100%;
            height: 100%;
        }
        ` : ''}
        .invoice-page {
         -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    transform: translateZ(0); /* Force hardware acceleration */
            ${isMultiUp ? '' : 'page-break-after: always;'}
            width: ${isMultiUp ? '90mm' : '100mm'};
            height: ${isMultiUp ? '140mm' : '144mm'};
            ${isMultiUp ? 'margin: 0 auto;' : 'margin: 0 auto;'}
            ${isMultiUp ? 'break-inside: avoid;' : ''}
            box-sizing: border-box;
        }
        .wrapper {
        
         box-shadow: none !important;
    transform: scale(1) !important;
            border: 2px solid black;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .section {
            border-bottom: 1px solid black;
            padding: 5px;
            margin: 0;
        }
        .section:last-child {
            border-bottom: none;
        }
        .header-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td,
        .footer-table td {
            padding: 2px;
            vertical-align: top;
        }
        .items-container {
            max-height: ${isMultiUp ? '40mm' : '52mm'};
            overflow: auto;
        }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: ${isMultiUp ? '8px' : '9px'}; }
        .cod-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cod-amount {
            font-size: ${isMultiUp ? '14px' : '18px'};
            font-weight: bold;
        }
        @media print {
            body {
            -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;

                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
    `;

    // Generate HTML for all invoices
    let allInvoicesHTML = '';

    if (isMultiUp) {
        allInvoicesHTML += '<div class="multi-up-container">';
    }

    invoices.forEach((invoiceData, index) => {
        // Generate unique class name for this invoice's items table
        const itemsTableClass = `items-table-${index}`;

        // Generate item-specific CSS based on this invoice's item count
        const itemRowStyle = getItemRowStyle(invoiceData.invoice_items.length, itemsTableClass, isMultiUp);

        // Add invoice-specific CSS and HTML
        allInvoicesHTML += `
        <div class="invoice-page">
            <style>${itemRowStyle}</style>
            ${generateSingleInvoiceHTML(invoiceData, itemsTableClass, isMultiUp)}
        </div>`;

        // For multi-up, we need to close the container after every 4 invoices
        if (isMultiUp && (index + 1) % 4 === 0 && index !== invoices.length - 1) {
            allInvoicesHTML += '</div><div class="multi-up-container">';
        }
    });

    if (isMultiUp) {
        allInvoicesHTML += '</div>';
    }

    // Final HTML content
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${invoices.length > 1 ? 'Print Multiple Invoices' : 'Print Invoice'}</title>
    ${commonCSS}
</head>
<body onload="${isMultiUp ? '' : 'window.print(); setTimeout(() => window.close(), 100);'}">
    ${allInvoicesHTML}
</body>
</html>`;
}
// Helper function to generate item row style based on item count
function getItemRowStyle(itemCount, tableClass, isMultiUp = false) {
    let baseSize = isMultiUp ? 10 : 12;
    let padding = isMultiUp ? '1px' : '2px';

    if (itemCount > 4 && itemCount <= 6) {
        return `
            .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: ${padding};
                font-size: ${baseSize - 4}px;
                text-align: left;
            }
        `;
    } else if (itemCount >= 7 && itemCount <= 9) {
        return `
        .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: ${padding};
                font-size: ${baseSize - 4}px;
                text-align: left;
            }
        `;
    } else if (itemCount > 9 && itemCount <= 15) {
        return `
        .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: ${isMultiUp ? '0.5px' : '1px'};
                font-size: ${baseSize - 5}px;
                text-align: left;
            }
        `;
    } else if (itemCount > 15) {
        return `
        .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: ${isMultiUp ? '0.3px' : '0.5px'};
                font-size: ${baseSize - 6}px;
                text-align: left;
            }
        `;
    } else {
        return `
        .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: ${padding};
                font-size: ${baseSize}px;
                text-align: left;
            }
        `;
    }
}

// Function to generate HTML for a single invoice
function generateSingleInvoiceHTML(invoiceData, itemsTableClass = 'items-table') {
    // Your existing invoice HTML generation logic
    const distributor = invoiceData.distributor || {};
    const {
        distributer_name = '',
            distributer_address = '',
            mobile: dist_mobile = '',
            email: dist_email = '',
            note: dist_note = ''
    } = distributor;

    if (typeof invoiceData === 'string') {
        try {
            invoiceData = JSON.parse(invoiceData);
        } catch (e) {
            console.error("Invalid invoice data", e);
            return '';
        }
    }

    const {
        full_name = '', address1 = '', address2 = '', village = '', district = '',
            sub_district = '', post_name = '', mobile = '', mobile2 = '',
            pincode = '', total_amount = 0, advanced_payment = 0,
            customer_id = 0,
            employee_name = '', created_at = '', id = '', invoice_items = []
    } = invoiceData;

    const codAmount = total_amount;
    const orderDate = new Date(created_at).toLocaleDateString('en-GB');

    // Function to convert number to words
    function numberToWords(num) {
        const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if (num === 0) return 'Zero Rupees';

        function convertLessThanOneThousand(n) {
            if (n === 0) return '';
            if (n < 20) return ones[n];
            const digit = n % 10;
            if (n < 100) return tens[Math.floor(n / 10)] + (digit ? ' ' + ones[digit] : '');
            return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' and ' + convertLessThanOneThousand(n % 100) :
                '');
        }

        let result = '';
        if (num >= 10000000) {
            result += convertLessThanOneThousand(Math.floor(num / 10000000)) + ' Crore ';
            num %= 10000000;
        }
        if (num >= 100000) {
            result += convertLessThanOneThousand(Math.floor(num / 100000)) + ' Lakh ';
            num %= 100000;
        }
        if (num >= 1000) {
            result += convertLessThanOneThousand(Math.floor(num / 1000)) + ' Thousand ';
            num %= 1000;
        }
        if (num > 0) {
            result += convertLessThanOneThousand(num);
        }
        return result.trim() + ' Rupees';
    }

    const codAmountInWords = numberToWords(codAmount);

    const itemsHTML = invoice_items.map((item) => `
        <tr>
            <td>${item.sku}</td>
            <td>${item.product_name}</td>
            <td>${item.quantity}</td>
            <td>${item.weight || 'N/A'}gm</td>
            <td>₹${item.price}</td>
        </tr>
    `).join('');

    return `
    <div class="wrapper">
        <div class="section" style="padding: 0;">
            <div style="display: flex; border-top: 1px solid black; border-bottom: 1px solid black;">
                <!-- COD Section -->
                <div style="flex: 2; padding: 3px; border-right: 1px solid black; margin: 0;">
                    <div style="font-size: 18px; font-weight: bold ; ">SPEED POST COD  <span style="font-size: 24px; font-weight: bold;">${codAmount}/-</span></div>
                    <div style="font-size: 12px; text-align:center;">${codAmountInWords} Only</div>
                </div>

                <!-- Customer ID Section -->
                <div style="flex: 1; padding: 3px; margin: 0;">
                    <div style="font-size: 14px; font-weight: bold; text-align:center">CUSTOMER ID</div>
                    <div style="font-size: 20px; font-weight: bold; text-align:center">${customer_id}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="bold"><b>To:</div>
            <div style="text-transform: uppercase; font-size: 15px;">${full_name}</div></b>
            <div style="font-size: 15px;">${address1}${address2 ? ', ' + address2 : ''}</div>
            <div style="font-size: 15px;">${village}, ${sub_district}, ${district}${pincode ? ' - ' + pincode : ''}</div>
            <div class="bold" style="font-size: 15px;">MOBILE NO: ${mobile}${mobile2 ? ' / ' + mobile2 : ''}</div>
        </div>

        <div class="section">
            <table class="header-table">
                <tr>
                    <td><strong>Order date :</strong> ${orderDate}</td>
                    <td><strong>Order by :</strong> ${employee_name}</td>
                </tr>
            </table>
        </div>

        <div class="section items-container">



        <div class="bold " style="margin-bottom:5px; font-size:8px;" >
            Invoice ID : ${id}
        </div>
            <table class="${itemsTableClass}">
                <thead>
                    <tr>
                        <th style>SKU</th>
                        <th>Item Name</th>
                        <th>Qty.</th>
                        <th>Weight</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                    <tr>
                        <td colspan="4" class="bold">Order Total</td>
                        <td class="bold">₹${codAmount}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table class="footer-table">
                <tr><td><b>Pickup and Return Address:</b></td></tr>
                <tr><td><strong>${distributer_name}  - ${dist_mobile}</strong></td></tr>
                <tr><td>${distributer_address}</td></tr>
                <tr><td></td></tr>
            </table>
        </div>

        <div class="section small">
            <b>Note : </b>
            ${dist_note}
        </div>
    </div>`;
}

// For Mahavir Courier printing
function printMahavirCourierInvoices() {
    const selectedCheckboxes = document.querySelectorAll('input[name="selected[]"]:checked');

    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one invoice to print.');
        return;
    }

    const invoiceIds = Array.from(selectedCheckboxes).map(cb => cb.value);

    fetch('get_invoices.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                invoice_ids: invoiceIds
            })
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to fetch invoices');
            }
            return res.json();
        })
        .then(invoices => {
            fetch('get_distributor.php')
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Failed to fetch distributor');
                    }
                    return res.json();
                })
                .then(distributor => {
                    invoices.forEach(invoice => {
                        invoice.distributor = distributor;
                    });
                    const htmlContent = generateMahavirPrintPageHtml(invoices);
                    generatePdfFromHtml(htmlContent,
                        `Mahavir_Invoices_${new Date().toISOString().slice(0,10)}.pdf`);
                })
                .catch(err => {
                    console.error('Failed to fetch distributor:', err);
                    alert('Failed to load distributor info. Please try again.');
                });
        })
        .catch(err => {
            console.error('Failed to fetch invoices:', err);
            alert('Failed to load selected invoices. Please try again.');
        });
}

function generatePdfFromHtml(htmlContent, filename, isMultiUp = false) {
    // Create a temporary div to hold our HTML
    const tempDiv = document.createElement('div');
    tempDiv.style.position = 'absolute';
    tempDiv.style.left = '-9999px';
    tempDiv.style.width = isMultiUp ? '190mm' : '105mm';
    tempDiv.innerHTML = htmlContent;
    document.body.appendChild(tempDiv);

    // Initialize jsPDF
    const {
        jsPDF
    } = window.jspdf;
    const pdf = new jsPDF({
        orientation: isMultiUp ? 'portrait' : 'portrait',
        unit: 'mm',
        format: isMultiUp ? 'a4' : [105, 148]
    });

    // Configure margins
    const margin = 2; // 5mm margin around each invoice
    const singlePageWidth = 105 - (margin * 2); // Adjusted width with margins
    const singlePageHeight = 148 - (margin * 2); // Adjusted height with margins

    // Get all invoice pages
    const pages = tempDiv.querySelectorAll('.invoice-page');

    // Configure html2canvas options for better quality
    const html2canvasOptions = {
        scale: 3,
        logging: false,
        useCORS: true,
        allowTaint: true,
        letterRendering: true,
        backgroundColor: '#FFFFFF'
    };

    // For multi-up, we need to handle layout differently
    if (isMultiUp) {
        // Multi-up configuration with margins
        const multiPageWidth = 90; // mm (original size)
        const multiPageHeight = 140; // mm (original size)
        const multiMargin = 5; // mm between invoices

        // Create a canvas for each page and arrange them 2x2
        const promises = Array.from(pages).map(page => {
            return html2canvas(page, html2canvasOptions);
        });

        Promise.all(promises).then(canvases => {
            // Calculate positions for 2x2 grid with margins
            const positions = [{
                    x: margin,
                    y: margin
                }, // Top-left
                {
                    x: margin + multiPageWidth + multiMargin,
                    y: margin
                }, // Top-right
                {
                    x: margin,
                    y: margin + multiPageHeight + multiMargin
                }, // Bottom-left
                {
                    x: margin + multiPageWidth + multiMargin,
                    y: margin + multiPageHeight + multiMargin
                } // Bottom-right
            ];

            // Arrange 4 invoices per A4 page (2x2)
            for (let i = 0; i < canvases.length; i += 4) {
                if (i > 0) pdf.addPage();

                // Add up to 4 invoices per page
                for (let j = 0; j < 4 && (i + j) < canvases.length; j++) {
                    const canvas = canvases[i + j];
                    const pos = positions[j];
                    if (canvas) {
                        const imgData = canvas.toDataURL('image/jpeg', 1.0);
                        pdf.addImage(imgData, 'JPEG', pos.x, pos.y, multiPageWidth, multiPageHeight, null,
                            'FAST');
                    }
                }
            }

            // Save the PDF
            pdf.save(filename);
            document.body.removeChild(tempDiv);
        }).catch(error => {
            console.error('Error generating PDF:', error);
            document.body.removeChild(tempDiv);
        });
    } else {
        // Single invoice per page with margins
        const promises = Array.from(pages).map(page => {
            return html2canvas(page, html2canvasOptions);
        });

        Promise.all(promises).then(canvases => {
            canvases.forEach((canvas, index) => {
                if (index > 0) pdf.addPage();
                const imgData = canvas.toDataURL('image/jpeg', 1.0);
                pdf.addImage(imgData, 'JPEG', margin, margin, singlePageWidth, singlePageHeight, null,
                    'FAST');
            });

            // Save the PDF
            pdf.save(filename);
            document.body.removeChild(tempDiv);
        }).catch(error => {
            console.error('Error generating PDF:', error);
            document.body.removeChild(tempDiv);
        });
    }
}

// Generate HTML for Mahavir Courier printing (without COD section)
function generateMahavirPrintPageHtml(invoices) {
    // Generate CSS that will apply to all invoices
    const commonCSS = `
    <style>
        @page {
            size: 105mm 148mm;
            margin: 2mm;
        }
        body {
        -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .invoice-page {
            -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    transform: translateZ(0); /* Force hardware acceleration */
            page-break-after: always;
            width: 105mm;
            height: 148mm;
            margin: 0 auto;
        }
        .invoice-page:last-child {
            page-break-after: auto;
        }
        .wrapper {
         box-shadow: none !important;
    transform: scale(1) !important;
            border: 2px solid black;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .section {
            border-bottom: 1px solid black;
            padding: 5px;
            margin: 0;
        }
        .section:last-child {
            border-bottom: none;
        }
        .header-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td,
        .footer-table td {
            padding: 2px;
            vertical-align: top;
        }
        .items-container {
            max-height: 65mm;
            overflow: auto;
        }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: 9px; }
    </style>
    `;

    // Generate HTML for all invoices
    let allInvoicesHTML = '';

    invoices.forEach((invoiceData, index) => {
        // Generate unique class name for this invoice's items table
        const itemsTableClass = `items-table-${index}`;

        // Generate item-specific CSS based on this invoice's item count
        const itemRowStyle = getItemRowStyle(invoiceData.invoice_items.length, itemsTableClass);

        // Add invoice-specific CSS and HTML
        allInvoicesHTML += `
        <div class="invoice-page">
            <style>${itemRowStyle}</style>
            ${generateMahavirInvoiceHTML(invoiceData, itemsTableClass)}
        </div>`;
    });

    // Final HTML content
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mahavir Courier Print</title>
    ${commonCSS}
</head>
<body onload="window.print(); setTimeout(() => window.close(), 100);">
    ${allInvoicesHTML}
</body>
</html>`;
}

// Function to generate HTML for a single Mahavir Courier invoice (without COD section)
function generateMahavirInvoiceHTML(invoiceData, itemsTableClass = 'items-table') {
    const distributor = invoiceData.distributor || {};
    const {
        distributer_name = '',
            distributer_address = '',
            mobile: dist_mobile = '',
            email: dist_email = '',
            note: dist_note = ''
    } = distributor;

    if (typeof invoiceData === 'string') {
        try {
            invoiceData = JSON.parse(invoiceData);
        } catch (e) {
            console.error("Invalid invoice data", e);
            return '';
        }
    }

    const {
        full_name = '', address1 = '', address2 = '', village = '', district = '',
            sub_district = '', post_name = '', mobile = '', mobile2 = '',
            pincode = '', total_amount = 0, advanced_payment = 0,
            customer_id = 0,
            employee_name = '', created_at = '', id = '', invoice_items = []
    } = invoiceData;

    const orderDate = new Date(created_at).toLocaleDateString('en-GB');

    const itemsHTML = invoice_items.map((item) => `
        <tr>
            <td>${item.sku}</td>
            <td>${item.product_name}</td>
            <td>${item.quantity}</td>
            <td>${item.weight || 'N/A'}gm</td>
            <td>₹${item.price}</td>
        </tr>
    `).join('');

    return `
    <div class="wrapper">
        <div class="section">
            <div class="bold"><b>To:</div>
            <div style="text-transform: uppercase; font-size: 15px;">${full_name}</div></b>
            <div style="font-size: 15px;">${address1}${address2 ? ', ' + address2 : ''}</div>
            <div style="font-size: 15px;">${village}, ${sub_district}, ${district}${pincode ? ' - ' + pincode : ''}</div>
            <div class="bold" style="font-size: 15px;">MOBILE NO: ${mobile}${mobile2 ? ' / ' + mobile2 : ''}</div>
        </div>

        <div class="section">
            <table class="header-table">
                <tr>
                    <td><strong>Order date :</strong> ${orderDate}</td>
                    <td><strong>Order by :</strong> ${employee_name}</td>
                </tr>

            </table>
        </div>

        <div class="section items-container">

            <table class="${itemsTableClass}">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Item Name</th>
                        <th>Qty.</th>
                        <th>Weight</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                    <tr>
                        <td colspan="4" class="bold">Order Total</td>
                        <td class="bold">₹${total_amount}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="bold">
            INVOICE ID : ${id}
        </div>

        <div class="section">
            <table class="footer-table">
                <tr><td><b>Pickup and Return Address:</b></td></tr>
                <tr><td><strong>${distributer_name}  - ${dist_mobile}</strong></td></tr>
                <tr><td>${distributer_address}</td></tr>
                <tr><td></td></tr>
            </table>
        </div>

        <div class="section small">
            <b>Note : </b>
            ${dist_note}
        </div>
    </div>`;
}

function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const messageDiv = document.createElement('div');

    // Set classes based on message type
    const bgColor = type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        'bg-blue-500';

    messageDiv.className = `${bgColor} text-white px-4 py-2 rounded shadow-lg mb-2 flex justify-between items-center`;
    messageDiv.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white font-bold">&times;</button>
    `;

    container.appendChild(messageDiv);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// For error messages with multiple lines (like your validation errors)
function showErrorMessages(errors) {
    const container = document.getElementById('message-container');
    const messageDiv = document.createElement('div');

    messageDiv.className = 'bg-red-500 text-white px-4 py-2 rounded shadow-lg mb-2';

    let html = '<div class="font-bold mb-1">Please fix the following errors:</div><ul class="list-disc pl-5">';
    errors.forEach(error => {
        html += `<li>${error}</li>`;
    });
    html += '</ul>';

    messageDiv.innerHTML = html +
        '<button onclick="this.parentElement.remove()" class="mt-2 text-white font-bold float-right">&times;</button>';

    container.appendChild(messageDiv);

    // Auto-remove after 8 seconds (longer for error messages)
    setTimeout(() => {
        messageDiv.remove();
    }, 8000);
}
</script>