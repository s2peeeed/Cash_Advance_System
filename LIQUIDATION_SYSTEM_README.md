# Liquidation System Documentation

## Overview
The Liquidation System allows administrators to process multiple liquidations for each cash advance, supporting both first and second liquidations when the initial liquidation amount doesn't cover the full cash advance amount.

## Features

### 1. Multiple Liquidation Support
- **First Liquidation**: Initial liquidation entry for a cash advance
- **Second Liquidation**: Additional liquidation when first liquidation doesn't cover the full amount
- **Automatic Balance Calculation**: System automatically calculates remaining balance after each liquidation
- **Liquidation Numbering**: Automatic tracking of liquidation sequence (1st, 2nd, etc.)

### 2. Liquidation Modal
- **Auto-filled Fields**: Employee details, cash advance information, voucher/cheque numbers
- **Admin Input Fields**: Amount liquidated, reference number, JEV number, date submitted, remarks
- **Real-time Validation**: Prevents over-liquidation and validates required fields
- **Balance Tracking**: Shows remaining balance and prevents exceeding cash advance amount

### 3. Liquidation History
- **Comprehensive Records**: View all liquidation entries with detailed information
- **Statistics Dashboard**: Total liquidations, amounts, first vs second liquidations
- **Search Functionality**: Search by name, type, reference number, or JEV number
- **Visual Indicators**: Color-coded cards for first vs second liquidations

## Database Schema

### Main Table: `liquidation_records`
```sql
CREATE TABLE `liquidation_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cash_advance_id` int(11) NOT NULL,
  `liquidation_number` int(11) NOT NULL DEFAULT 1,
  `employee_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `voucher_number` varchar(50) DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `cash_advance_amount` decimal(10,2) NOT NULL,
  `amount_liquidated` decimal(10,2) NOT NULL,
  `remaining_balance` decimal(10,2) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `jev_number` varchar(100) DEFAULT NULL,
  `date_submitted` date NOT NULL,
  `submitted_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`cash_advance_id`) REFERENCES `granted_cash_advances` (`id`) ON DELETE CASCADE
);
```

### Key Fields Explained
- **liquidation_number**: Sequential number (1 for first, 2 for second, etc.)
- **cash_advance_amount**: Original cash advance amount (for reference)
- **amount_liquidated**: Amount being liquidated in this entry
- **remaining_balance**: Balance remaining after this liquidation
- **reference_number**: Admin-provided reference number
- **jev_number**: Journal Entry Voucher number
- **status**: Current status of the liquidation (pending/approved/rejected)

## How It Works

### 1. First Liquidation Process
1. Admin clicks "Liquidation Details" button in pending.php
2. Modal opens with auto-filled cash advance information
3. Admin enters liquidation details (amount, reference, JEV, date, remarks)
4. System validates amount doesn't exceed remaining balance
5. Liquidation record is created with liquidation_number = 1
6. If fully liquidated, cash advance status changes to 'completed'

### 2. Second Liquidation Process
1. If first liquidation doesn't cover full amount, remaining balance > 0
2. Admin can process second liquidation through same modal
3. System automatically sets liquidation_number = 2
4. Shows previous liquidation history in modal
5. Validates against remaining balance from first liquidation

### 3. Balance Calculation
- **Initial Balance**: Cash advance amount
- **After First Liquidation**: Initial balance - amount_liquidated
- **After Second Liquidation**: Previous remaining_balance - amount_liquidated
- **Completion**: When remaining_balance <= 0

## Files Involved

### Core Files
1. **`pending.php`** - Main pending liquidations page with liquidation modal
2. **`liquidation_modal.php`** - AJAX handler for modal operations
3. **`liquidation_history.php`** - Liquidation records history page
4. **`setup_liquidation_system.php`** - Database setup script

### Database Files
1. **`create_liquidation_records_table.sql`** - Table creation script
2. **`dashboard.php`** - Updated with liquidation history link

## Setup Instructions

### 1. Run Database Setup
```bash
# Access the setup script in your browser
http://your-domain/setup_liquidation_system.php
```

### 2. Verify Installation
- Check that `liquidation_records` table exists
- Verify foreign key constraint is working
- Test modal functionality in pending.php

### 3. Access Points
- **Pending Liquidations**: `pending.php` - Click "Liquidation Details" button
- **Liquidation History**: `liquidation_history.php` - View all liquidation records
- **Dashboard Link**: Added to main navigation menu

## Usage Examples

### Example 1: First Liquidation
- Cash Advance Amount: ₱10,000.00
- First Liquidation: ₱7,000.00
- Remaining Balance: ₱3,000.00
- Status: Still pending (not fully liquidated)

### Example 2: Second Liquidation
- Previous Remaining Balance: ₱3,000.00
- Second Liquidation: ₱3,000.00
- Final Remaining Balance: ₱0.00
- Status: Completed (fully liquidated)

### Example 3: Partial Second Liquidation
- Previous Remaining Balance: ₱3,000.00
- Second Liquidation: ₱2,000.00
- Final Remaining Balance: ₱1,000.00
- Status: Still pending (can have third liquidation)

## Best Practices

### 1. Data Entry
- Always verify amounts before submission
- Use descriptive reference numbers
- Include relevant remarks for audit trail
- Set appropriate submission dates

### 2. Validation
- System prevents over-liquidation
- Required fields are enforced
- Amount validation against remaining balance
- Date validation for submission

### 3. Record Keeping
- All liquidations are permanently recorded
- Audit trail includes admin who processed
- Timestamps for creation and updates
- Status tracking for approval workflow

## Troubleshooting

### Common Issues
1. **Modal not loading**: Check AJAX permissions and database connection
2. **Validation errors**: Verify amount doesn't exceed remaining balance
3. **Foreign key errors**: Ensure cash advance exists in granted_cash_advances table
4. **Permission denied**: Verify admin role and session

### Error Messages
- "Amount liquidated cannot exceed remaining balance" - Reduce liquidation amount
- "Cash advance not found" - Verify cash advance ID exists
- "Database error" - Check database connection and table structure

## Future Enhancements

### Potential Features
1. **Approval Workflow**: Multi-level approval process
2. **Email Notifications**: Automatic notifications for liquidations
3. **Document Upload**: Attach supporting documents
4. **Bulk Operations**: Process multiple liquidations at once
5. **Advanced Reporting**: Detailed liquidation analytics
6. **Audit Trail**: Enhanced activity logging

### Integration Points
1. **Accounting System**: Export to accounting software
2. **Document Management**: Integration with document storage
3. **Workflow Engine**: Advanced approval workflows
4. **Reporting Tools**: Integration with BI tools

## Support

For technical support or questions about the liquidation system:
1. Check this documentation first
2. Verify database setup and permissions
3. Review error logs for specific issues
4. Contact system administrator for database issues 