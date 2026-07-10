using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class ExpenseRepository : IExpenseRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Expense GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Expenses WHERE Id = @Id;";
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

        public IEnumerable<Expense> GetAll()
        {
            var list = new List<Expense>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Expenses ORDER BY Id DESC;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReader(reader));
                }
            }
            return list;
        }

        public void Add(Expense entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Expenses (ExpenseDate, Category, Amount, Description, ReceiptAttachment, IsPersonal)
                    VALUES (@ExpenseDate, @Category, @Amount, @Description, @ReceiptAttachment, @IsPersonal);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    AddParameters(cmd, entity);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Expense entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Expenses SET
                        ExpenseDate = @ExpenseDate, Category = @Category, Amount = @Amount,
                        Description = @Description, ReceiptAttachment = @ReceiptAttachment, IsPersonal = @IsPersonal
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
                string query = "DELETE FROM Expenses WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Expense MapReader(SqliteDataReader reader)
        {
            return new Expense
            {
                Id = Convert.ToInt32(reader["Id"]),
                ExpenseDate = reader["ExpenseDate"]?.ToString() ?? "",
                Category = reader["Category"]?.ToString() ?? "سایر",
                Amount = Convert.ToDecimal(reader["Amount"]),
                Description = reader["Description"]?.ToString() ?? "",
                ReceiptAttachment = reader["ReceiptAttachment"]?.ToString() ?? "",
                IsPersonal = Convert.ToInt32(reader["IsPersonal"]) == 1
            };
        }

        private void AddParameters(SqliteCommand cmd, Expense entity)
        {
            cmd.Parameters.AddWithValue("@ExpenseDate", entity.ExpenseDate);
            cmd.Parameters.AddWithValue("@Category", entity.Category);
            cmd.Parameters.AddWithValue("@Amount", entity.Amount);
            cmd.Parameters.AddWithValue("@Description", entity.Description);
            cmd.Parameters.AddWithValue("@ReceiptAttachment", entity.ReceiptAttachment);
            cmd.Parameters.AddWithValue("@IsPersonal", entity.IsPersonal ? 1 : 0);
        }
    }
}