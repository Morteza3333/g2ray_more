using System;
using System.Collections.Generic;
using Microsoft.Data.Sqlite;
using FinAccApp.Models;

namespace FinAccApp.Data.Repositories
{
    public class NotificationRepository : INotificationRepository
    {
        private readonly string _connectionString = DatabaseInitializer.ConnectionString;

        public Notification GetById(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Notifications WHERE Id = @Id;";
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

        public IEnumerable<Notification> GetAll()
        {
            var list = new List<Notification>();
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "SELECT * FROM Notifications ORDER BY Id DESC;";
                using (var cmd = new SqliteCommand(query, conn))
                using (var reader = cmd.ExecuteReader())
                {
                    while (reader.Read()) list.Add(MapReader(reader));
                }
            }
            return list;
        }

        public void Add(Notification entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    INSERT INTO Notifications (Title, Description, ShamsiDate, IsRead, Type)
                    VALUES (@Title, @Description, @ShamsiDate, @IsRead, @Type);
                    SELECT last_insert_rowid();";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Title", entity.Title);
                    cmd.Parameters.AddWithValue("@Description", entity.Description);
                    cmd.Parameters.AddWithValue("@ShamsiDate", entity.ShamsiDate);
                    cmd.Parameters.AddWithValue("@IsRead", entity.IsRead ? 1 : 0);
                    cmd.Parameters.AddWithValue("@Type", entity.Type);
                    entity.Id = Convert.ToInt32(cmd.ExecuteScalar());
                }
            }
        }

        public void Update(Notification entity)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = @"
                    UPDATE Notifications SET
                        Title = @Title, Description = @Description, ShamsiDate = @ShamsiDate,
                        IsRead = @IsRead, Type = @Type
                    WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", entity.Id);
                    cmd.Parameters.AddWithValue("@Title", entity.Title);
                    cmd.Parameters.AddWithValue("@Description", entity.Description);
                    cmd.Parameters.AddWithValue("@ShamsiDate", entity.ShamsiDate);
                    cmd.Parameters.AddWithValue("@IsRead", entity.IsRead ? 1 : 0);
                    cmd.Parameters.AddWithValue("@Type", entity.Type);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        public void Delete(int id)
        {
            using (var conn = new SqliteConnection(_connectionString))
            {
                conn.Open();
                string query = "DELETE FROM Notifications WHERE Id = @Id;";
                using (var cmd = new SqliteCommand(query, conn))
                {
                    cmd.Parameters.AddWithValue("@Id", id);
                    cmd.ExecuteNonQuery();
                }
            }
        }

        private Notification MapReader(SqliteDataReader reader)
        {
            return new Notification
            {
                Id = Convert.ToInt32(reader["Id"]),
                Title = reader["Title"]?.ToString() ?? "",
                Description = reader["Description"]?.ToString() ?? "",
                ShamsiDate = reader["ShamsiDate"]?.ToString() ?? "",
                IsRead = Convert.ToInt32(reader["IsRead"]) == 1,
                Type = reader["Type"]?.ToString() ?? "اطلاع‌رسانی"
            };
        }
    }
}