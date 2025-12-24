import csv
import sys
from openpyxl import Workbook

if len(sys.argv) < 3:
    print("Usage: python3 csv_to_xlsx.py <input_csv> <output_xlsx>")
    sys.exit(1)

input_csv = sys.argv[1]
output_xlsx = sys.argv[2]

wb = Workbook()
ws = wb.active

try:
    with open(input_csv, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for row in reader:
            ws.append(row)
    wb.save(output_xlsx)
    print(f"Successfully converted {input_csv} to {output_xlsx}")
except Exception as e:
    print(f"Error converting: {e}")
    sys.exit(1)
