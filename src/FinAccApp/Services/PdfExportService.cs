using System;
using System.Collections.Generic;
using System.IO;
using System.Text.Json;
using QuestPDF.Fluent;
using QuestPDF.Helpers;
using QuestPDF.Infrastructure;
using FinAccApp.Models;
using FinAccApp.Helpers;

namespace FinAccApp.Services
{
    public class PdfExportService
    {
        static PdfExportService()
        {
            QuestPDF.Settings.License = LicenseType.Community;
        }

        public void ExportInvoiceToPdf(Invoice invoice, string outputPath)
        {
            var items = new List<InvoiceItem>();
            try
            {
                items = JsonSerializer.Deserialize<List<InvoiceItem>>(invoice.ItemsJson) ?? new List<InvoiceItem>();
            }
            catch { }

            Document.Create(container =>
            {
                container.Page(page =>
                {
                    page.Size(PageSizes.A4);
                    page.Margin(2, Unit.Centimetre);
                    page.PageColor(Colors.White);
                    page.DefaultTextStyle(x => x.FontFamily("Tahoma").FontSize(11));

                    page.Header()
                        .Row(row =>
                        {
                            row.RelativeItem().Column(column =>
                            {
                                column.Item().Text("فاکتور فروش").FontSize(20).Bold().FontColor(Colors.Blue.Medium);
                                column.Item().Text($"شماره فاکتور: {invoice.InvoiceNumber}").FontSize(12);
                                column.Item().Text($"تاریخ شمسی: {invoice.ShamsiDate}").FontSize(12);
                            });

                            row.ConstantItem(100).AlignRight().Column(column =>
                            {
                                column.Item().Text("استودیو شمس").FontSize(14).Bold();
                                column.Item().Text("مدیریت مالی").FontSize(10).FontColor(Colors.Grey.Medium);
                            });
                        });

                    page.Content()
                        .PaddingVertical(1, Unit.Centimetre)
                        .Column(column =>
                        {
                            column.Item().BorderBottom(1).BorderColor(Colors.Grey.Lighten1).PaddingBottom(5).Row(row =>
                            {
                                row.RelativeItem().Text($"نام مشتری: {invoice.ClientName}").Bold();
                                row.RelativeItem().AlignRight().Text($"تلفن تماس: {invoice.ClientPhone}");
                            });

                            column.Item().PaddingTop(10);

                            column.Item().Table(table =>
                            {
                                table.ColumnsDefinition(columns =>
                                {
                                    columns.ConstantColumn(40);
                                    columns.RelativeColumn(3);
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                });

                                table.Header(header =>
                                {
                                    header.Cell().Background(Colors.Grey.Lighten3).Padding(5).Text("#");
                                    header.Cell().Background(Colors.Grey.Lighten3).Padding(5).Text("توضیحات");
                                    header.Cell().Background(Colors.Grey.Lighten3).Padding(5).Text("تعداد");
                                    header.Cell().Background(Colors.Grey.Lighten3).Padding(5).Text("قیمت واحد (تومان)");
                                    header.Cell().Background(Colors.Grey.Lighten3).Padding(5).Text("مجموع");
                                });

                                int idx = 1;
                                foreach (var item in items)
                                {
                                    table.Cell().Padding(5).Text(idx.ToString());
                                    table.Cell().Padding(5).Text(item.Description);
                                    table.Cell().Padding(5).Text(item.Quantity.ToString());
                                    table.Cell().Padding(5).Text(item.UnitPrice.ToString("N0"));
                                    table.Cell().Padding(5).Text(item.Total.ToString("N0"));
                                    idx++;
                                }
                            });

                            column.Item().PaddingTop(20);

                            column.Item().AlignRight().Column(totalCol =>
                            {
                                totalCol.Item().Text($"تخفیف: {invoice.Discount:N0} تومان");
                                totalCol.Item().Text($"مالیات ({invoice.TaxPercentage}%): {(invoice.Total * invoice.TaxPercentage / 100):N0} تومان");
                                totalCol.Item().Text($"مجموع نهایی فاکتور: {invoice.Total:N0} تومان").Bold().FontSize(14);
                                totalCol.Item().Text($"مبلغ باقی‌مانده: {invoice.Remaining:N0} تومان").FontColor(Colors.Red.Medium);
                            });
                        });

                    page.Footer()
                        .AlignCenter()
                        .Text(x =>
                        {
                            x.Span("صفحه ");
                            x.CurrentPageNumber();
                        });
                });
            }).GeneratePdf(outputPath);
        }

        public void ExportReportToPdf(string reportTitle, List<string[]> tableHeaders, List<string[]> tableData, string outputPath)
        {
            Document.Create(container =>
            {
                container.Page(page =>
                {
                    page.Size(PageSizes.A4);
                    page.Margin(1.5f, Unit.Centimetre);
                    page.PageColor(Colors.White);
                    page.DefaultTextStyle(x => x.FontFamily("Tahoma").FontSize(10));

                    page.Header()
                        .Row(row =>
                        {
                            row.RelativeItem().Column(column =>
                            {
                                column.Item().Text(reportTitle).FontSize(18).Bold().FontColor(Colors.Purple.Medium);
                                column.Item().Text($"تاریخ گزارش: {JalaliDateHelper.GetCurrentShamsiDate()}").FontSize(10);
                            });
                        });

                    page.Content()
                        .PaddingVertical(1, Unit.Centimetre)
                        .Column(column =>
                        {
                            column.Item().Table(table =>
                            {
                                table.ColumnsDefinition(columns =>
                                {
                                    if (tableHeaders.Count > 0)
                                    {
                                        for (int i = 0; i < tableHeaders[0].Length; i++)
                                        {
                                            columns.RelativeColumn();
                                        }
                                    }
                                });

                                table.Header(header =>
                                {
                                    foreach (var headerCell in tableHeaders[0])
                                    {
                                        header.Cell().Background(Colors.Purple.Lighten4).Padding(5).Text(headerCell).Bold();
                                    }
                                });

                                foreach (var rowData in tableData)
                                {
                                    foreach (var cellText in rowData)
                                    {
                                        table.Cell().BorderBottom(0.5f).BorderColor(Colors.Grey.Lighten2).Padding(5).Text(cellText);
                                    }
                                }
                            });
                        });

                    page.Footer()
                        .AlignCenter()
                        .Text(x =>
                        {
                            x.Span("گزارش مالی - استودیو شمس | صفحه ");
                            x.CurrentPageNumber();
                        });
                });
            }).GeneratePdf(outputPath);
        }
    }

    public class InvoiceItem
    {
        public string Description { get; set; } = string.Empty;
        public int Quantity { get; set; }
        public decimal UnitPrice { get; set; }
        public decimal Total => Quantity * UnitPrice;
    }
}