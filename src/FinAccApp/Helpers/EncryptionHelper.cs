using System.IO;
using System.Security.Cryptography;
using System.Text;

namespace FinAccApp.Helpers
{
    public static class EncryptionHelper
    {
        public static string ComputeSha256Hash(string rawData)
        {
            using (SHA256 sha256Hash = SHA256.Create())
            {
                byte[] bytes = sha256Hash.ComputeHash(Encoding.UTF8.GetBytes(rawData));
                StringBuilder builder = new StringBuilder();
                for (int i = 0; i < bytes.Length; i++)
                {
                    builder.Append(bytes[i].ToString("x2"));
                }
                return builder.ToString();
            }
        }

        public static void EncryptFile(string inputFile, string outputFile, string key)
        {
            byte[] keyBytes = Encoding.UTF8.GetBytes(key.PadRight(32).Substring(0, 32));
            byte[] ivBytes = Encoding.UTF8.GetBytes(key.PadRight(16).Substring(0, 16));

            using (Aes aesAlg = Aes.Create())
            {
                aesAlg.Key = keyBytes;
                aesAlg.IV = ivBytes;

                using (FileStream fsOut = new FileStream(outputFile, FileMode.Create))
                using (ICryptoTransform encryptor = aesAlg.CreateEncryptor(aesAlg.Key, aesAlg.IV))
                using (CryptoStream cs = new CryptoStream(fsOut, encryptor, CryptoStreamMode.Write))
                using (FileStream fsIn = new FileStream(inputFile, FileMode.Open))
                {
                    fsIn.CopyTo(cs);
                }
            }
        }

        public static void DecryptFile(string inputFile, string outputFile, string key)
        {
            byte[] keyBytes = Encoding.UTF8.GetBytes(key.PadRight(32).Substring(0, 32));
            byte[] ivBytes = Encoding.UTF8.GetBytes(key.PadRight(16).Substring(0, 16));

            using (Aes aesAlg = Aes.Create())
            {
                aesAlg.Key = keyBytes;
                aesAlg.IV = ivBytes;

                using (FileStream fsOut = new FileStream(outputFile, FileMode.Create))
                using (ICryptoTransform decryptor = aesAlg.CreateDecryptor(aesAlg.Key, aesAlg.IV))
                using (CryptoStream cs = new CryptoStream(fsOut, decryptor, CryptoStreamMode.Write))
                using (FileStream fsIn = new FileStream(inputFile, FileMode.Open))
                {
                    fsIn.CopyTo(cs);
                }
            }
        }
    }
}