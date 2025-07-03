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
function printInvoice(invoiceData) {
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
            const htmlContent = generatePrintPageHtml([invoiceData]);
            openPrintWindow(htmlContent);
        })
        .catch(err => {
            console.error('Failed to fetch distributor:', err);
            alert('Failed to load distributor info. Please try again.');
        });
}

// For multiple invoice printing
function printSelectedInvoices() {
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
                const htmlContent = generatePrintPageHtml(invoices);
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

// Common function to open print window
function openPrintWindow(htmlContent) {
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    if (!printWindow) {
        alert('Popup window was blocked. Please allow popups for this site and try again.');
        return;
    }
    
    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
}

// Common function to generate HTML for printing (single or multiple invoices)
function generatePrintPageHtml(invoices) {
    // Generate CSS that will apply to all invoices
    const commonCSS = `
    <style>
        @page {
            size: 105mm 148mm;
            margin: 2mm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .invoice-page {
            page-break-after: always;
            width: 105mm;
            height: 148mm;
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
            max-height: 52mm;
            overflow: auto;
        }
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .small { font-size: 9px; }
        .cod-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cod-amount {
            font-size: 18px;
            font-weight: bold;
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
            ${generateSingleInvoiceHTML(invoiceData, itemsTableClass)}
        </div>`;
    });
    
    // Final HTML content
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${invoices.length > 1 ? 'Print Multiple Invoices' : 'Print Invoice'}</title>
    ${commonCSS}
</head>
<body onload="window.print(); setTimeout(() => window.close(), 100);">
    ${allInvoicesHTML}
</body>
</html>`;
}
// Helper function to generate item row style based on item count
function getItemRowStyle(itemCount, tableClass) {
    if (itemCount > 4 && itemCount <= 6) {
        return `
            .${tableClass}{
                width:100%;
                border-collapse: collapse;
            }
            .${tableClass} th, .${tableClass} td {
                border: 1px solid black;
                padding: 1.5px;
                font-size: 10px;
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
                padding: 1.5px;
                font-size: 8px;
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
                padding: 1px;
                font-size: 7px;
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
                padding: 0.5px;
                font-size: 6px;
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
                padding: 2px;
                font-size: 12px;
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
                <div style="flex: 2; padding: 3px; border-right: 1px solid black; margin: 0;">
                    <div style="font-size: 18px; font-weight: bold;">SPEED POST COD  <span style="font-size: 24px; font-weight: bold;">${codAmount}/-</span></div>
                    <div style="font-size: 12px; text-align:center">${codAmountInWords} Only</div>
                </div>
                
                <!-- Customer ID Section -->
                <div style="flex: 1; padding: 3px; margin: 0;">
                    <div style="font-size: 16px; font-weight: bold; text-align:center">CUSTOMER ID</div>
                    <div style="font-size: 16px; font-weight: bold; text-align:center">${customer_id}</div>
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
            size: 105mm 148mm;
            margin: 2mm;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .invoice-page {
            page-break-after: always;
            width: 105mm;
            height: 148mm;
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