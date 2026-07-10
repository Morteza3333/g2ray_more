using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class InvoiceRepository : IInvoiceRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Invoice GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Invoices WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    using (var reader = cmd.ExecuteReader())
                    {
                        if (reader.Read()) return MapReader(reader);
                    }
                }
            }
            return null!;
        }

        public IEnumerable<Invoice> GetAll()
        {
            var list = new List<Invoice>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Invoices ORDER BY Id DESC;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReader(reader));
                }
            }
            return list;
        }

        public void Add(Invoice entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Invoices (InvoiceNumber, ShamsiDate, ClientName, ClientPhone, ItemsJson, Discount, TaxPercentage, Total, Remaining, QRCodePath)
                    VALUES (@InvoiceNumber, @ShamsiDate, @ClientName, @ClientPhone, @ItemsJson, @Discount, @TaxPercentage, @Total, @Remaining, @QRCodePath);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    AddParameters(cmd, entity);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Invoice entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Invoices SET
                        InvoiceNumber = @InvoiceNumber, ShamsiDate = @ShamsiDate, ClientName = @ClientName,
                        ClientPhone = @ClientPhone, ItemsJson = @ItemsJson, Discount = @Discount,
                        TaxPercentage = @TaxPercentage, Total = @Total, Remaining = @Remaining, QRCodePath = @QRCodePath
                    WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", entity.Id);
                    AddParameters(cmd, entity);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        public void Delete(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "DELETE FROM Invoices WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Invoice MapReader(SqliteDataReader reader)
        {
            return new Invoice
            {
                Id = Convert.ToInt32(reader["Id"]),
                InvoiceNumber = reader["InvoiceNumber"]?.ToString() ?? "",
                ShamsiDate = reader["ShamsiDate"]?.ToString() ?? "",
                ClientName = reader["ClientName"]?.ToString() ?? "",
                ClientPhone = reader["ClientPhone"]?.ToString() ?? "",
                ItemsJson = reader["ItemsJson"]?.ToString() ?? "[]",
                Discount = Convert.ToDecimal(reader["Discount"]),
                TaxPercentage = Convert.ToDecimal(reader["TaxPercentage"]),
                Total = Convert.ToDecimal(reader["Total"]),
                Remaining = Convert.ToDecimal(reader["Remaining"]),
                QRCodePath = reader["QRCodePath"]?.ToString() ?? ""
            };
        }

        private void AddParameters(SqliteCommand cmd, Invoice entity)
        {
            cmd.Parameters.AddWithValue("@InvoiceNumber", entity.InvoiceNumber);
            cmd.Parameters.AddWithValue("@ShamsiDate", entity.ShamsiDate);
            cmd.Parameters.AddWithValue("@ClientName", entity.ClientName);
            cmd.Parameters.AddWithValue("@ClientPhone", entity.ClientPhone);
            cmd.Parameters.AddWithValue("@ItemsJson", entity.ItemsJson);
            cmd.Parameters.AddWithValue("@Discount", entity.Discount);
            cmd.Parameters.AddWithValue("@TaxPercentage", entity.TaxPercentage);
            cmd.Parameters.AddWithValue("@Total", entity.Total);
            cmd.Parameters.AddWithValue("@Remaining", entity.Remaining);
            cmd.Parameters.AddWithValue("@QRCodePath", entity.QRCodePath);
        }
    }
}