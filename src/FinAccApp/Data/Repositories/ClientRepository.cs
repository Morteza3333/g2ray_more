using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class ClientRepository : IClientRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Client GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Clients WHERE Id = @Id;";
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

        public IEnumerable<Client> GetAll()
        {
            var list = new List<Client>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Clients ORDER BY Id DESC;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReader(reader));
                }
            }
            return list;
        }

        public void Add(Client entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Clients (ClientName, Phone, Email, Address, Company, Projects, TotalPaid, RemainingBalance, Notes)
                    VALUES (@ClientName, @Phone, @Email, @Address, @Company, @Projects, @TotalPaid, @RemainingBalance, @Notes);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    AddParameters(cmd, entity);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Client entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Clients SET
                        ClientName = @ClientName, Phone = @Phone, Email = @Email, Address = @Address,
                        Company = @Company, Projects = @Projects, TotalPaid = @TotalPaid,
                        RemainingBalance = @RemainingBalance, Notes = @Notes
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
                string query = "DELETE FROM Clients WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Client MapReader(SqliteDataReader reader)
        {
            return new Client
            {
                Id = Convert.ToInt32(reader["Id"]),
                ClientName = reader["ClientName"]?.ToString() ?? "",
                Phone = reader["Phone"]?.ToString() ?? "",
                Email = reader["Email"]?.ToString() ?? "",
                Address = reader["Address"]?.ToString() ?? "",
                Company = reader["Company"]?.ToString() ?? "",
                Projects = reader["Projects"]?.ToString() ?? "",
                TotalPaid = Convert.ToDecimal(reader["TotalPaid"]),
                RemainingBalance = Convert.ToDecimal(reader["RemainingBalance"]),
                Notes = reader["Notes"]?.ToString() ?? ""
            };
        }

        private void AddParameters(SqliteCommand cmd, Client entity)
        {
            cmd.Parameters.AddWithValue("@ClientName", entity.ClientName);
            cmd.Parameters.AddWithValue("@Phone", entity.Phone);
            cmd.Parameters.AddWithValue("@Email", entity.Email);
            cmd.Parameters.AddWithValue("@Address", entity.Address);
            cmd.Parameters.AddWithValue("@Company", entity.Company);
            cmd.Parameters.AddWithValue("@Projects", entity.Projects);
            cmd.Parameters.AddWithValue("@TotalPaid", entity.TotalPaid);
            cmd.Parameters.AddWithValue("@RemainingBalance", entity.RemainingBalance);
            cmd.Parameters.AddWithValue("@Notes", entity.Notes);
        }
    }
}