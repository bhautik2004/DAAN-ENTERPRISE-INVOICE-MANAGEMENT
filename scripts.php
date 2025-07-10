<script>
// Enable double-click to edit barcode
document.addEventListener('DOMContentLoaded', function() {
    const barcodeCells = document.querySelectorAll('td:nth-child(7)'); // 7th column is barcode
    
    barcodeCells.forEach(cell => {
        cell.addEventListener('dblclick', function() {
            const originalValue = this.textContent.trim();
            const invoiceId = this.closest('tr').querySelector('input[name="selected[]"]').value;
            
            this.innerHTML = `
                <form class="flex" onsubmit="saveBarcode(event, ${invoiceId})">
                    <input type="text" value="${originalValue}" class="w-full p-1 border" id="barcode-input-${invoiceId}">
                    <button type="submit" class="bg-blue-500 text-white px-2 ml-1">Save</button>
                </form>
            `;
            document.getElementById(`barcode-input-${invoiceId}`).focus();
        });
    });
});

function saveBarcode(event, invoiceId) {
    event.preventDefault();
    const newBarcode = event.target.querySelector('input').value;
    
    fetch('update_barcode.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            invoice_id: invoiceId,
            barcode: newBarcode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the cell display
            event.target.closest('td').textContent = newBarcode;
            showMessage('Barcode updated successfully!', 'success');
        } else {
            showMessage('Error updating barcode: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Error updating barcode: ' + error, 'error');
    });
}

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
            // Generate the HTML for single invoice
            const htmlContent = generatePrintPageHtml([invoiceData], isMultiUp);
            openPrintWindow(htmlContent, isMultiUp);
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
        body: JSON.stringify({ invoice_ids: invoiceIds })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to fetch invoices');
        }
        return res.json();
    })
    .then(invoices => {
        // First fetch distributor info
        fetch('get_distributor.php')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to fetch distributor');
                }
                return res.json();
            })
            .then(distributor => {
                // Add distributor to each invoice
                invoices.forEach(invoice => {
                    invoice.distributor = distributor;
                });
                
                // Generate the HTML for all invoices
                const htmlContent = generatePrintPageHtml(invoices, isMultiUp);
                openPrintWindow(htmlContent, isMultiUp);
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
            size: A4;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: ${isMultiUp ? '12px' : '14px'};
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        ${isMultiUp ? `
        .multi-up-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-gap: 15mm;
            width: 100%;
            height: 100%;
            padding: 5mm;
            box-sizing: border-box;
        }
        ` : ''}
        .invoice-page {
            ${isMultiUp ? '' : 'page-break-after: always;'}
            width: ${isMultiUp ? '90mm' : '190mm'};
            height: ${isMultiUp ? '140mm' : '277mm'};
            ${isMultiUp ? 'margin: 0;' : 'margin: 0 auto;'}
            ${isMultiUp ? 'break-inside: avoid;' : ''}
            box-sizing: border-box;
        }
        .wrapper {
            border: 2px solid black;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            background-color: white; /* Ensure white background */
        }
        .section {
            border-bottom: 1px solid black;
            padding: ${isMultiUp ? '3px' : '5px'};
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
            max-height: ${isMultiUp ? '50mm' : '160mm'};
            overflow: auto;
            margin: ${isMultiUp ? '2px 0' : '3px 0'};
        }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: ${isMultiUp ? '10px' : '12px'}; }
        .cod-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cod-amount {
            font-size: ${isMultiUp ? '16px' : '28px'};
            font-weight: bold;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }
            .invoice-page {
                margin: 0 auto;
                box-shadow: 0 0 0 2px black; /* Ensure border prints */
            }
            ${isMultiUp ? `
            .multi-up-container {
                page-break-after: always;
            }
            ` : ''}
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
    let baseSize = isMultiUp ? 20 : 25; // Increased base font size
    let padding = isMultiUp ? '3px' : '4px'; // Increased padding
    
    if (itemCount > 4 && itemCount <= 6) {
        return `
            .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: ${padding};
                font-size: ${baseSize - 1}px;
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
                padding: ${isMultiUp ? '2px' : '3px'};
                font-size: ${baseSize - 3}px;
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
                padding: ${isMultiUp ? '1px' : '2px'};
                font-size: ${baseSize - 4}px;
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
function generateSingleInvoiceHTML(invoiceData, itemsTableClass = 'items-table', isMultiUp = false) {
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
            return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' and ' + convertLessThanOneThousand(n % 100) : '');
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
                <div style="flex: 2; padding: 8px; border-right: 1px solid black; margin: 0;">
                    <div style="font-size:34px; font-weight: bold;">SPEED POST COD  <span style="font-size: 38px; font-weight: bold;">${codAmount}/-</span></div>
                    <div style="font-size: 20px; text-align:center">${codAmountInWords} Only</div>
                </div>

                <!-- Customer ID Section -->
                <div style="flex: 1; padding: 8px; margin: 0;">
                    <div style="font-size: 25px; font-weight: bold; text-align:center">CUSTOMER ID</div>
                    <div style="font-size: 35px; font-weight: bold; text-align:center">${customer_id}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="bold" style="font-size: 30px;"><b>To:</div>
            <div style="text-transform: uppercase; font-size: 30px;">${full_name}</div></b>
            <div style="font-size: 28px;">${address1}${address2 ? ', ' + address2 : ''}</div>
            <div style="font-size: 28px;">${village}, ${sub_district}, ${district}${pincode ? ' - ' + pincode : ''}</div>
            <div class="bold" style="font-size: 28px;">MOBILE NO: ${mobile}${mobile2 ? ' / ' + mobile2 : ''}</div>
        </div>

        <div class="section">
            <table class="header-table">
                <tr>
                    <td style="font-size: 24px"><strong>Order date :</strong> ${orderDate}</td>
                    <td style="font-size: 24px"><strong>Order by :</strong> ${employee_name}</td>
                </tr>
            </table>
        </div>

        <div class="section items-container">
            <div class="bold" style="font-size:25px">
                Invoice ID : ${id}
            </div>
            <table class="${itemsTableClass}">
                <thead>
                    <tr>
                        <th style="font-size: ${isMultiUp ? '24px' : '26px'}">SKU</th>
                        <th style="font-size: ${isMultiUp ? '24px' : '26px'}">Item Name</th>
                        <th style="font-size: ${isMultiUp ? '24px' : '26px'}">Qty.</th>
                        <th style="font-size: ${isMultiUp ? '24px' : '26px'}">Weight</th>
                        <th style="font-size: ${isMultiUp ? '24px' : '26px'}">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                    <tr>
                        <td colspan="4" class="bold" style="font-size: ${isMultiUp ? '24px' : '26px'}">Order Total</td>
                        <td class="bold" style="font-size: ${isMultiUp ? '24px' : '26px'}">₹${codAmount}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table class="footer-table">
                <tr><td style="font-size: 26px"><b>Pickup and Return Address:</b></td></tr>
                <tr><td style="font-size: 26px"><strong>${distributer_name}  - ${dist_mobile}</strong></td></tr>
                <tr><td style="font-size: 26px">${distributer_address}</td></tr>
                <tr><td></td></tr>
            </table>
        </div>

        <div class="section small"style="font-size: 20px" >
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
        body: JSON.stringify({ invoice_ids: invoiceIds })
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to fetch invoices');
        }
        return res.json();
    })
    .then(invoices => {
        // First fetch distributor info
        fetch('get_distributor.php')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to fetch distributor');
                }
                return res.json();
            })
            .then(distributor => {
                // Add distributor to each invoice
                invoices.forEach(invoice => {
                    invoice.distributor = distributor;
                });

                // Generate the HTML for all invoices
                const htmlContent = generateMahavirPrintPageHtml(invoices);
                openPrintWindow(htmlContent);
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

// Generate HTML for Mahavir Courier printing (without COD section)
function generateMahavirPrintPageHtml(invoices) {
    // Generate CSS that will apply to all invoices
    const commonCSS = `
    <style>
        @page {
            size: A4;
            margin: ;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 14px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-page {
            page-break-after: always;
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
        }
        .invoice-page:last-child {
            page-break-after: auto;
        }
        .wrapper {
            border: 2px solid black;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 10px;
        }
        .section {
            border-bottom: 1px solid black;
            padding: 8px;
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
            padding: 4px;
            vertical-align: top;
            font-size: 14px;
        }
        .items-container {
            max-height: 180mm;
            overflow: auto;
        }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: 12px; }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .invoice-page {
                margin: 0 auto;
            }
        }
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
            <div style="text-transform: uppercase; font-size: 16px;">${full_name}</div></b>
            <div style="font-size: 16px;">${address1}${address2 ? ', ' + address2 : ''}</div>
            <div style="font-size: 16px;">${village}, ${sub_district}, ${district}${pincode ? ' - ' + pincode : ''}</div>
            <div class="bold" style="font-size: 16px;">MOBILE NO: ${mobile}${mobile2 ? ' / ' + mobile2 : ''}</div>
        </div>

        <div class="section">
            <table class="header-table">
                <tr>
                    <td style="font-size: 14px"><strong>Order date :</strong> ${orderDate}</td>
                    <td style="font-size: 14px"><strong>Order by :</strong> ${employee_name}</td>
                </tr>
            </table>
        </div>

        <div class="section items-container">
            <div class="bold" style="font-size: 16px">
                INVOICE ID : ${id}
            </div>
            <table class="${itemsTableClass}">
                <thead>
                    <tr>
                        <th style="font-size: 14px">SKU</th>
                        <th style="font-size: 14px">Item Name</th>
                        <th style="font-size: 14px">Qty.</th>
                        <th style="font-size: 14px">Weight</th>
                        <th style="font-size: 14px">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHTML}
                    <tr>
                        <td colspan="4" class="bold" style="font-size: 14px">Order Total</td>
                        <td class="bold" style="font-size: 14px">₹${total_amount}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table class="footer-table">
                <tr><td style="font-size: 14px"><b>Pickup and Return Address:</b></td></tr>
                <tr><td style="font-size: 14px"><strong>${distributer_name}  - ${dist_mobile}</strong></td></tr>
                <tr><td style="font-size: 14px">${distributer_address}</td></tr>
                <tr><td></td></tr>
            </table>
        </div>

        <div class="section small" style="font-size: 14px">
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