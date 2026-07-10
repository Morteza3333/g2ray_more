using System;
using System.Globalization;

namespace FinAccApp.Helpers
{
    public static class JalaliDateHelper
    {
        private static readonly PersianCalendar pc = new PersianCalendar();

        public static string ToShamsi(DateTime dt)
        {
            int year = pc.GetYear(dt);
            int month = pc.GetMonth(dt);
            int day = pc.GetDayOfMonth(dt);
            return $"{year:0000}/{month:00}/{day:00}";
        }

        public static string GetCurrentShamsiDate()
        {
            return ToShamsi(DateTime.Now);
        }

        public static DateTime ToGregorian(string shamsiDate)
        {
            if (string.IsNullOrWhiteSpace(shamsiDate))
                return DateTime.Now;

            try
            {
                string[] parts = shamsiDate.Split('/');
                if (parts.Length != 3) return DateTime.Now;

                int year = int.Parse(parts[0]);
                int month = int.Parse(parts[1]);
                int day = int.Parse(parts[2]);

                return pc.ToDateTime(year, month, day, 0, 0, 0, 0);
            }
            catch
            {
                return DateTime.Now;
            }
        }

        public static int GetDaysDifference(string shamsiDate1, string shamsiDate2)
        {
            DateTime dt1 = ToGregorian(shamsiDate1);
            DateTime dt2 = ToGregorian(shamsiDate2);
            return (int)(dt2 - dt1).TotalDays;
        }

        public static string AddDays(string shamsiDate, int days)
        {
            DateTime dt = ToGregorian(shamsiDate);
            DateTime dtNew = dt.AddDays(days);
            return ToShamsi(dtNew);
        }
    }
}