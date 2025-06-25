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


// <!-- Column Toggle Script -->
// function enableEdit(id) {
//     document.querySelectorAll('#row_' + id + ' .editable').forEach(el => el.disabled = false);
//     document.querySelector('#row_' + id + ' .save-btn').classList.remove('hidden');
// }



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

function printInvoice(invoiceData) {
    let addressHTML = `
        <tr>
            <td colspan="3" style="padding: 3px; font-family: Calibri; font-size: 18px; font-weight: 700; border:none;">
                ${invoiceData.address1}
            </td>
        </tr>`;

    if (invoiceData.address2) {
        addressHTML += `
        <tr>
            <td colspan="3" style="padding: 3px; font-family: Calibri; font-size: 18px; font-weight: 700; border:none;">
                ${invoiceData.address2}
            </td>
        </tr>`;
    }

    let printWindow = window.open('', '', 'width=800,height=600');

    if (!printWindow) {
        alert("Popup blocked! Please allow pop-ups and try again.");
        return;
    }

    let invoiceHTML = `
    <!DOCTYPE html>
    <html lang="gu">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shipping Label Form</title>
        <style>
            @page {
                size: 105mm 148mm; /* Exact A6 dimensions */
                margin: 0; /* No default margins */
            }
            body {
                margin: 0;
                padding: 2mm; /* Safe inner padding */
                width: 101mm; /* 105mm - 4mm total padding */
                height: 144mm; /* 148mm - 4mm total padding */
                font-size: 14px;
                box-sizing: border-box;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .invoice-container {
                width: 100%;
                height: 100%;
                overflow: hidden; /* Prevent any overflow */
            }
            table {
                width: 100%;
                height: 100%;
                border-collapse: collapse;
                border: 2px solid black;
                box-sizing: border-box;
                table-layout: fixed;
            }
            td {
                padding: 2px;
                font-family: Calibri;
                font-size: 14px;
                font-weight: 700;
                border: 2px solid black;
                word-wrap: break-word;
            }
            .compact-row {
                height: 18px;
            }
            .small-text {
                font-size: 12px;
                line-height: 1.2;
            }
            /* Column width adjustments */
            td:nth-child(1) { /* Customer No column */
                width: 40%;
            }
            td:nth-child(2) { /* COD column */
                width: 15%;
            }
            td:nth-child(3) { /* Amount column */
                width: 45%;
            }
        </style>
    </head>
    <body onload="window.print(); setTimeout(() => window.close(), 100);">
        <div class="invoice-container">
            <table>
                <tr class="compact-row">
                    <td align="center" colspan="3" style="font-family: Bahnschrift; font-weight: 700; font-size: 16px;">
                        BOOK UNDER SPEED POST COD BNPL SERVICE
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" style="font-family: Bahnschrift; font-size: 14px;">
                        CUSTOMER NO
                    </td>
                    <td align="center" rowspan="2" style="font-family: Bahnschrift; font-size: 30px;">
                        COD
                    </td>
                    <td align="center" rowspan="2" style="font-size: 30px; font-family: Palatino Linotype;">
                        ${invoiceData.total_amount - invoiceData.advanced_payment}/-
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" style="background-color: black; color:white; font-family: Bahnschrift;">
                        56759
                    </td>
                </tr>
                <tr>
                    <td align="center" colspan="3" style="font-family: Calibri; font-weight: 800; font-size: 38px;">
                        મુ - ${invoiceData.village} 
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        ${invoiceData.full_name}
                    </td>
                </tr>
                ${addressHTML}
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        પોસ્ટ - ${invoiceData.post_name}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        તાલુકો - ${invoiceData.sub_district}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        જીલ્લો - ${invoiceData.district}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        પિન - ${invoiceData.pincode}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        મો - ${invoiceData.mobile} ${invoiceData.mobile2 ? '/ ' + invoiceData.mobile2 : ''}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="small-text" style="padding: 3px;">
                        🚗 SHIPPED BY- PRS HOMOEO PHARMACY, 406, SANKALP ICON, OPP PARIKH HOSPITAL, NEW NIKOL, AHMEDABAD, 382350<br> 📞 MO-79849 30709
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="small-text" style="padding: 3px;">
                        ⚠️ ટપાલી ને નોંધ - ખાસ ફોન કરીને કુરિયર આપવા જવું. જો ડેટા બતાવતો ના હોય તો કાયદા પ્રમાણે 2-3 દિવસ માટે કુરિયર પોસ્ટ ઓફિસ મા જ રાખવું અને પછી ફરી વાર ડિલિવરી આપવાની રહેશે. જો કોઈ ખોટા કારણ થી કુરિયર રિટર્ન થસે તો કાયદેસર ની કાર્યવાહી કરવામાં આવશે.
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" colspan="3" style="font-family: Bahnschrift; font-size: 14px;">
                        ${invoiceData.product_name} - ${invoiceData.quantity} / ${invoiceData.employee_name}
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>
    `;

    printWindow.document.open();
    printWindow.document.write(invoiceHTML);
    printWindow.document.close();
}

// function enableEdit(id) {
//     const row = document.getElementById(`row_${id}`);
//     const editables = row.querySelectorAll('.editable');
//     editables.forEach(input => input.disabled = false);
//     row.querySelector('.edit-btn').classList.add('hidden');
//     row.querySelector('.save-btn').classList.remove('hidden');
// }

function printSelectedInvoices() {
    // Get all selected checkboxes
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');

    // Check if at least one invoice is selected
    if (selected.length === 0) {
        alert('Please select at least one invoice to print.');
        return;
    }

    // Array to hold all invoice HTMLs
    let allInvoicesHTML = '';

    // Loop through selected invoices and collect their HTML
    selected.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const invoiceData = {
            mobile: row.querySelector('input[name="mobile"]').value,
            full_name: row.querySelector('input[name="full_name"]').value,
            address1: row.querySelector('input[name="address1"]').value,
            address2: row.querySelector('input[name="address2"]').value,
            pincode: row.querySelector('input[name="pincode"]').value,
            district: row.querySelector('input[name="district"]').value,
            sub_district: row.querySelector('input[name="sub_district"]').value,
            village: row.querySelector('input[name="village"]').value,
            post_name: row.querySelector('input[name="post_name"]').value,
            mobile2: row.querySelector('input[name="mobile2"]').value,
            product_name: row.querySelector('input[name="product_name"]').value, 
            quantity: row.querySelector('input[name="quantity"]').value,
            employee_name: row.querySelector('input[name="employee_name"]').value,
            total_amount: row.querySelector('input[name="total_amount"]').value,
            advanced_payment: row.querySelector('input[name="advanced_payment"]').value,
            created_at:row.querySelector('input[name="created_at"]').value,
            status: row.querySelector('select[name="status"]').value
        };

        // Generate invoice HTML for this invoice
        allInvoicesHTML += generateInvoiceHTML(invoiceData);
    });

    // Open a new window and print all invoices together
    printAllInvoices(allInvoicesHTML);
}

function generateInvoiceHTML(invoiceData) {
    let addressHTML = `
        <tr>
            <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                ${invoiceData.address1}
            </td>
        </tr>`;

    if (invoiceData.address2) {
        addressHTML += `
        <tr>
           <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                ${invoiceData.address2}
            </td>
        </tr>`;
    }

    return `
      <div class="invoice-container">
            <table>
                <tr class="compact-row">
                    <td align="center" colspan="3" style="font-family: Bahnschrift; font-weight: 700; font-size: 16px;">
                        BOOK UNDER SPEED POST COD BNPL SERVICE
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" style="font-family: Bahnschrift; font-size: 14px;">
                        CUSTOMER NO
                    </td>
                    <td align="center" rowspan="2" style="font-family: Bahnschrift; font-size: 30px;">
                        COD
                    </td>
                    <td align="center" rowspan="2" style="font-size: 30px; font-family: Palatino Linotype;">
                        ${invoiceData.total_amount - invoiceData.advanced_payment}/-
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" style="background-color: black; color:white; font-family: Bahnschrift;">
                        56759
                    </td>
                </tr>
                <tr>
                    <td align="center" colspan="3" style="font-family: Calibri; font-weight: 800; font-size: 38px;">
                        મુ - ${invoiceData.village} 
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                        ${invoiceData.full_name}
                    </td>
                </tr>
                ${addressHTML}
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                        પોસ્ટ - ${invoiceData.post_name}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                        તાલુકો - ${invoiceData.sub_district}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                        જીલ્લો - ${invoiceData.district}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                        પિન - ${invoiceData.pincode}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 20px; padding: 2px; border:none;">
                        મો - ${invoiceData.mobile} ${invoiceData.mobile2 ? '/ ' + invoiceData.mobile2 : ''}
                    </td>
                </tr>
                <tr>
                   <td colspan="3" class="small-medium" style="padding: 3px;">
    🚗 SHIPPED BY- PRS HOMOEO PHARMACY, 406, SANKALP ICON, OPP PARIKH HOSPITAL, NEW NIKOL, AHMEDABAD, 382350 <br>
    📞 MO-79849 30709
</td>

                </tr>
                <tr>
                    <td colspan="3" class="small-text" style="padding: 3px;">
                        ⚠️ ટપાલી ને નોંધ - ખાસ ફોન કરીને કુરિયર આપવા જવું. જો ડેટા બતાવતો ના હોય તો કાયદા પ્રમાણે 2-3 દિવસ માટે કુરિયર પોસ્ટ ઓફિસ મા જ રાખવું અને પછી ફરી વાર ડિલિવરી આપવાની રહેશે. જો કોઈ ખોટા કારણ થી કુરિયર રિટર્ન થસે તો કાયદેસર ની કાર્યવાહી કરવામાં આવશે.
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" colspan="3" style="font-family: Bahnschrift; font-size: 14px;">
                        ${invoiceData.product_name} - ${invoiceData.quantity} / ${invoiceData.employee_name}
                    </td>
                </tr>
            </table>
        </div>
    `;
}

function printAllInvoices(allInvoicesHTML) {
    // Open a new blank window
    let printWindow = window.open('', '', 'width=800,height=600');

    if (!printWindow) {
        alert("Popup blocked! Please allow pop-ups and try again.");
        return;
    }

    // Generate the full HTML structure for all invoices
    let fullHTML = `
<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label Form</title>
     <style>
            @page {
                size: 105mm 148mm; /* A6 dimensions */
                 margin: 2mm 0 0 0;
            }
            body {
                margin: 0;
                padding: 2mm;
                width: 100%;
                height: 100%;
                font-size: 14px;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }
            .invoice-container {
                width: calc(100% - 4mm); /* Account for body padding */
                height: calc(100% - 4mm);
                max-width: 101mm; /* Ensure equal left-right spacing */
                margin: 0 auto; /* Center horizontally */
            }
            table {
                width: 100%;
                height: 100%;
                border-collapse: collapse;
                border: 2px solid black;
                box-sizing: border-box;
                table-layout: fixed;
                margin: 0 auto; /* Center table */
            }
            td {
                padding: 2px;
                font-family: Calibri;
                font-size: 14px;
                font-weight: 700;
                border: 2px solid black;
                word-wrap: break-word;
            }
            .compact-row {
                height: 18px;
            }
            .small-text {
                font-size: 12px;
                line-height: 1.2;
            }
            /* Specific column adjustments */
            td:nth-child(1) { /* Customer No column */
                width: 40%;
            }
            td:nth-child(2) { /* COD column */
                width: 15%;
            }
            td:nth-child(3) { /* Amount column */
                width: 45%;
            }
        </style>
</head>
<body>
    ${allInvoicesHTML}
    <script>
        window.onload = function() {
                window.print();
                setTimeout(() => { window.close(); }, 500);
            };
            window.onafterprint = function() {
                window.close();
            };
    <\/script>
</body>
</html>`;

    // Write the invoice content and print
    printWindow.document.open();
    printWindow.document.write(fullHTML);
    printWindow.document.close();
}

function printCombinedInvoices() {
    // Get all selected checkboxes
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');

    // Check if at least two invoices are selected
    if (selected.length < 2) {
        alert('Please select at least two invoices to print combined.');
        return;
    }

    // Variables to store combined data
    let combinedTotal = 0;
    let combinedAdvanced = 0;
    let products = [];
    
    // Get data from first selected invoice (for common fields)
    const firstRow = selected[0].closest('tr');
    const invoiceData = {
        mobile: firstRow.querySelector('input[name="mobile"]').value,
        full_name: firstRow.querySelector('input[name="full_name"]').value,
        address1: firstRow.querySelector('input[name="address1"]').value,
        address2: firstRow.querySelector('input[name="address2"]').value,
        pincode: firstRow.querySelector('input[name="pincode"]').value,
        district: firstRow.querySelector('input[name="district"]').value,
        sub_district: firstRow.querySelector('input[name="sub_district"]').value,
        village: firstRow.querySelector('input[name="village"]').value,
        post_name: firstRow.querySelector('input[name="post_name"]').value,
        mobile2: firstRow.querySelector('input[name="mobile2"]').value,
        product_name: '', // Leave empty as we'll handle products separately
        quantity: '',     // Leave empty as we'll handle quantities separately
        employee_name: firstRow.querySelector('input[name="employee_name"]').value,
        total_amount: parseFloat(firstRow.querySelector('input[name="total_amount"]').value),
        advanced_payment: parseFloat(firstRow.querySelector('input[name="advanced_payment"]').value),
        created_at: firstRow.querySelector('input[name="created_at"]').value,
        status: firstRow.querySelector('select[name="status"]').value
    };

    // Add first invoice data to combined values
    combinedTotal += invoiceData.total_amount;
    combinedAdvanced += invoiceData.advanced_payment;
    products.push(`${firstRow.querySelector('input[name="product_name"]').value} (${firstRow.querySelector('input[name="quantity"]').value})`);

    // Process remaining selected invoices
    for (let i = 1; i < selected.length; i++) {
        const row = selected[i].closest('tr');
        const total = parseFloat(row.querySelector('input[name="total_amount"]').value);
        const advanced = parseFloat(row.querySelector('input[name="advanced_payment"]').value);
        const product = row.querySelector('input[name="product_name"]').value;
        const quantity = row.querySelector('input[name="quantity"]').value;

        combinedTotal += total;
        combinedAdvanced += advanced;
        products.push(`${product} (${quantity})`);
    }

    // Create combined data object
    const combinedData = {
        ...invoiceData,
        total_amount: combinedTotal,
        advanced_payment: combinedAdvanced,
        // We'll use the combined products string in the template
        combined_products: products.join(' + ')
    };

    // Generate and print the combined invoice
    printCombinedInvoiceTemplate(combinedData);
}
function printCombinedInvoiceTemplate(invoiceData) {
    // Open a new blank window
    let printWindow = window.open('', '', 'width=800,height=600');

    if (!printWindow) {
        alert("Popup blocked! Please allow pop-ups and try again.");
        return;
    }

    // Generate invoice structure with combined products
    let invoiceHTML = `
<!DOCTYPE html>
    <html lang="gu">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shipping Label Form</title>
        <style>
            @page {
                size: 105mm 148mm; /* Exact A6 dimensions */
                margin: 0; /* No default margins */
            }
            body {
                margin: 0;
                padding: 2mm; /* Safe inner padding */
                width: 101mm; /* 105mm - 4mm total padding */
                height: 144mm; /* 148mm - 4mm total padding */
                font-size: 14px;
                box-sizing: border-box;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .invoice-container {
                width: 100%;
                height: 100%;
                overflow: hidden; /* Prevent any overflow */
            }
            table {
                width: 100%;
                height: 100%;
                border-collapse: collapse;
                border: 2px solid black;
                box-sizing: border-box;
                table-layout: fixed;
            }
            td {
                padding: 2px;
                font-family: Calibri;
                font-size: 14px;
                font-weight: 700;
                border: 2px solid black;
                word-wrap: break-word;
            }
            .compact-row {
                height: 18px;
            }
            .small-text {
                font-size: 12px;
                line-height: 1.2;
            }
            /* Column width adjustments */
            td:nth-child(1) { /* Customer No column */
                width: 40%;
            }
            td:nth-child(2) { /* COD column */
                width: 15%;
            }
            td:nth-child(3) { /* Amount column */
                width: 45%;
            }
        </style>
    </head>
    <body onload="window.print(); setTimeout(() => window.close(), 100);">
        <div class="invoice-container">
            <table>
                <tr class="compact-row">
                    <td align="center" colspan="3" style="font-family: Bahnschrift; font-weight: 700; font-size: 16px;">
                        BOOK UNDER SPEED POST COD BNPL SERVICE
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" style="font-family: Bahnschrift; font-size: 14px;">
                        CUSTOMER NO
                    </td>
                    <td align="center" rowspan="2" style="font-family: Bahnschrift; font-size: 30px;">
                        COD
                    </td>
                    <td align="center" rowspan="2" style="font-size: 30px; font-family: Palatino Linotype;">
                        ${invoiceData.total_amount - invoiceData.advanced_payment}/-
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" style="background-color: black; color:white; font-family: Bahnschrift;">
                        56759
                    </td>
                </tr>
                <tr>
                    <td align="center" colspan="3" style="font-family: Calibri; font-weight: 800; font-size: 38px;">
                        મુ - ${invoiceData.village} 
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        ${invoiceData.full_name}
                    </td>
               </tr>
               <tr style="font-family: calibri; font-weight: 700; font-size: xx-large; ">
            <td colspan="3" style="padding: 5px; border:none;">
                ${invoiceData.address1}
            </td>
        </tr>
        ${invoiceData.address2 ? `<tr style="font-family: calibri; font-weight: 700; font-size: xx-large; ">
            <td colspan="3" style="padding: 5px; border:none;" >
                ${invoiceData.address2}
            </td>
        </tr>` : ''}
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        પોસ્ટ - ${invoiceData.post_name}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        તાલુકો - ${invoiceData.sub_district}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        જીલ્લો - ${invoiceData.district}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        પિન - ${invoiceData.pincode}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="font-family: calibri; font-size: 18px; padding: 2px; border:none;">
                        મો - ${invoiceData.mobile} ${invoiceData.mobile2 ? '/ ' + invoiceData.mobile2 : ''}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="small-text" style="padding: 3px;">
                        🚗 SHIPPED BY- PRS HOMOEO PHARMACY, 406, SANKALP ICON, OPP PARIKH HOSPITAL, NEW NIKOL, AHMEDABAD, 382350 <br> 📞 MO-79849 30709
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="small-text" style="padding: 3px;">
                        ⚠️ ટપાલી ને નોંધ - ખાસ ફોન કરીને કુરિયર આપવા જવું. જો ડેટા બતાવતો ના હોય તો કાયદા પ્રમાણે 2-3 દિવસ માટે કુરિયર પોસ્ટ ઓફિસ મા જ રાખવું અને પછી ફરી વાર ડિલિવરી આપવાની રહેશે. જો કોઈ ખોટા કારણ થી કુરિયર રિટર્ન થસે તો કાયદેસર ની કાર્યવાહી કરવામાં આવશે.
                    </td>
                </tr>
                <tr class="compact-row">
                    <td align="center" colspan="3" style="font-family: Bahnschrift; font-size: 14px;">
                      ${invoiceData.combined_products} / ${invoiceData.employee_name}
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>`;

    // Write the invoice content and print
    printWindow.document.open();
    printWindow.document.write(invoiceHTML);
    printWindow.document.close();
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
    
    messageDiv.innerHTML = html + '<button onclick="this.parentElement.remove()" class="mt-2 text-white font-bold float-right">&times;</button>';
    
    container.appendChild(messageDiv);
    
    // Auto-remove after 8 seconds (longer for error messages)
    setTimeout(() => {
        messageDiv.remove();
    }, 8000);
}

</script>