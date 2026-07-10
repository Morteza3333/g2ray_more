using System;
using System.Collections.Generic;
using ClosedXML.Excel;

namespace FinAccApp.Services
{
    public class ExcelExportService
    {
        public void ExportDataToExcel(string sheetName, string[] headers, List<string[]> dataRows, string outputPath)
        {
            using (var workbook = new XLWorkbook())
            {
                var worksheet = workbook.Worksheets.Add(sheetName);
                worksheet.RightToLeft = true;

                for (int col = 0; col < headers.Length; col++)
                {
                    var cell = worksheet.Cell(1, col + 1);
                    cell.Value = headers[col];
                    cell.Style.Font.Bold = true;
                    cell.Style.Fill.BackgroundColor = XLColor.LightPastelPurple;
                    cell.Style.Alignment.Horizontal = XLAlignmentHorizontalValues.Center;
                }

                for (int row = 0; row < dataRows.Count; row++)
                {
                    var rowData = dataRows[row];
                    for (int col = 0; col < rowData.Length; col++)
                    {
                        var cell = worksheet.Cell(row + 2, col + 1);
                        if (decimal.TryParse(rowData[col], out decimal dVal))
                        {
                            cell.Value = dVal;
                            cell.Style.NumberFormat.Format = "#,##0";
                        }
                        else if (double.TryParse(rowData[col], out double dbVal))
                        {
                            cell.Value = dbVal;
                        }
                        else
                        {
                            cell.Value = rowData[col];
                        }
                    }
                }

                worksheet.Columns().AdjustToContents();
                workbook.SaveAs(outputPath);
            }
        }
    }
}