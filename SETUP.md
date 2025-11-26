# WHMCS Bridge for Joomla 5 - Setup Guide

## Overview

WHMCS Bridge connects your Joomla 5 site to WHMCS, enabling:
- One-way user sync from WHMCS to Joomla (users matched by email)
- Authentication via WHMCS API (users login with their WHMCS password)
- Product syncing to track what products users have
- Group mapping to assign Joomla usergroups based on WHMCS products

## Installation

1. Go to **Joomla Admin > System > Install > Extensions**
2. Upload the `pkg_whmcsbridge_Joomla5_v1.0.0_*.zip` package
3. The package installs:
   - `com_whmcsbridge` - Main component
   - `plg_authentication_whmcs` - Authentication plugin

## WHMCS API Setup

### 1. Create API Credentials in WHMCS

1. Log into WHMCS Admin
2. Go to **Setup > Staff Management > API Credentials**
3. Click **Generate New API Credential**
4. Give it a name (e.g., "Joomla Bridge")
5. Copy the **Identifier** and **Secret** (the secret is only shown once!)

### 2. Set API Permissions

The API credential needs these permissions (found under **Setup > Staff Management > API Credentials > Edit > API Permissions**):

**Required - Clients Section:**
- GetClients
- GetClientsDetails

**Required - Products Section:**
- GetProducts
- GetClientsProducts

**Required for Login - Authentication Section:**
- ValidateLogin (may be listed as "Validate Login" in some versions)

**Note:** The permissions UI varies by WHMCS version. If you can't find a specific permission:
1. Try enabling "Full Administrator" access for the API credential
2. Or grant all permissions in the relevant section (Clients, Products, etc.)
3. Check WHMCS documentation for your specific version

### 3. Whitelist Server IP in WHMCS

1. Go to **Setup > General Settings > Security**
2. Find **API IP Access Restriction**
3. Add your Joomla server's IP address

To find your server's IP, run this command via SSH:
```bash
curl -4 ifconfig.me
```

## Joomla Configuration

Go to **Components > WHMCS Bridge > Options**

### Standard Setup (Different Servers)

If Joomla and WHMCS are on different servers:

| Setting | Value |
|---------|-------|
| API URL | `https://your-whmcs-domain.com` |
| API Identifier | Your WHMCS API Identifier |
| API Secret | Your WHMCS API Secret |
| Skip SSL Verification | No |

### Same-Server Setup (Behind Cloudflare)

If both Joomla and WHMCS are on the same server AND WHMCS is behind Cloudflare, you may encounter 403 errors. To bypass Cloudflare:

| Setting | Value |
|---------|-------|
| API URL | `https://YOUR.SERVER.IP.ADDRESS` (e.g., `https://50.28.37.63`) |
| API Identifier | Your WHMCS API Identifier |
| API Secret | Your WHMCS API Secret |
| Skip SSL Verification | **Yes** |
| WHMCS Hostname | Your WHMCS domain (e.g., `hosting.example.com`) |

**Why this works:**
- Using the IP bypasses Cloudflare's proxy
- Skip SSL is needed because the SSL cert is for the domain, not the IP
- The hostname tells the server which virtual host to serve (required for shared hosting)

### Sync Settings

| Setting | Description |
|---------|-------------|
| Default User Group | Joomla usergroup for newly created users |
| Auto-Create Users | Automatically create Joomla users during sync |
| Sync on Login | Update user data from WHMCS when they log in |

## Troubleshooting

### View API Log

Click **View API Log** on the Dashboard or go to the Log view to see all API calls and errors.

### Common Errors

#### HTTP 403 Forbidden

**Cause:** Request blocked before reaching WHMCS API

**Solutions:**
1. **IP Whitelist:** Add your server IP to WHMCS API IP Access Restriction
2. **Cloudflare:** If WHMCS is behind Cloudflare, see detailed Cloudflare section below
3. **ModSecurity:** Check if server firewall is blocking POST requests

### Cloudflare Configuration (Detailed)

If WHMCS is behind Cloudflare and you're getting 403 errors, you have several options:

#### Option 1: WAF Skip Rule (Recommended)

The component uses browser-like headers to avoid bot detection. Combined with a WAF skip rule, this reliably works through Cloudflare.

**Step 1: Create a WAF Custom Rule in Cloudflare**

1. Go to **Security > WAF > Custom rules**
2. Click **Create rule**
3. Rule name: `Allow WHMCS API from Server`
4. Expression:
```
(http.request.uri.path contains "/includes/api.php" and ip.src eq YOUR.SERVER.IP)
```
5. Action: **Skip** > Select all WAF rules

**Step 2: Configure the Bridge**

| Setting | Value |
|---------|-------|
| API URL | `https://your-whmcs-domain.com` (your normal WHMCS URL) |
| API Identifier | Your WHMCS API Identifier |
| API Secret | Your WHMCS API Secret |
| Skip SSL Verification | No |
| Direct Server IP | Leave empty |

**How it works:** The component sends browser-like headers (Chrome User-Agent, Accept headers) which helps pass Cloudflare's bot detection. The WAF skip rule ensures your server IP isn't blocked by other security rules.

#### Option 2: Bypass Cloudflare Using Direct IP

If Option 1 doesn't work, use the **Direct Server IP** field to bypass Cloudflare entirely:

| Setting | Value |
|---------|-------|
| API URL | `https://hosting.example.com` (your normal WHMCS URL) |
| API Identifier | Your WHMCS API Identifier |
| API Secret | Your WHMCS API Secret |
| Skip SSL Verification | No (unless you have SSL issues) |
| Direct Server IP | `50.28.37.63` (your server's actual IP) |

**How it works:** The component connects directly to the IP address you specify, but uses the domain name for SSL certificate validation and virtual hosting. This bypasses Cloudflare's proxy entirely while maintaining proper SSL and hostname routing.

**To find your server IP:** Run `curl -4 ifconfig.me` via SSH on your server.

#### Option 3: Legacy Method (IP in URL)

If Options 1 and 2 don't work, you can put the IP directly in the URL:
- API URL: `https://YOUR.SERVER.IP`
- Skip SSL Verification: Yes
- WHMCS Hostname: `your-whmcs-domain.com`

This method requires skipping SSL verification and may have issues with virtual hosting.

#### Additional Cloudflare Troubleshooting

If you're still getting 403 errors after creating the WAF rule:

**Check Security Events:**
1. Go to Cloudflare Dashboard > Security > Events
2. Look for blocked requests to `/includes/api.php`
3. Note which security feature is blocking

**Disable Additional Security Features (if needed):**

Some Cloudflare features can block API requests even with WAF rules:

- **Bot Fight Mode:** Security > Bots > Configure > Disable or create exception
- **Super Bot Fight Mode:** May require Business plan to create exceptions
- **Security Level:** Security > Settings > Reduce to "Low" for testing
- **Browser Integrity Check:** Security > Settings > Disable temporarily

**Create a Configuration Rule (Alternative):**

1. Go to **Rules > Configuration Rules**
2. Create rule with expression: `(http.request.uri.path contains "/includes/api.php")`
3. Settings:
   - Security Level: Essentially Off
   - Browser Integrity Check: Off
   - Bot Fight Mode: Off

#### Cloudflare Access (Enterprise Alternative)

Use Cloudflare Access with service tokens for authenticated API access.

#### Verifying the Fix

After making changes:
1. Clear the API log in Joomla
2. Click "Test Connection" on the Dashboard
3. Check the log for the connection result

**Still getting 403?** Check:
- Is the rule on the correct Cloudflare zone?
- Is there a parent zone with stricter rules?
- Check Security Events for what's still blocking
- Try Option 2 (Direct Server IP) as a reliable bypass

#### HTTP 404 Not Found

**Cause:** Server doesn't know which virtual host to serve

**Solution:** Set the **WHMCS Hostname** in settings when using an IP address for the API URL.

#### SSL Certificate Error

**Cause:** Using IP address but SSL cert is for domain name

**Solution:** Enable **Skip SSL Verification** (only safe for same-server connections)

#### IPv6 Issues

If your log shows an IPv6 address being rejected:

**Solution:** The component forces IPv4 by default. Ensure you've installed the latest package version.

### API Log Location

Logs are stored at:
```
/administrator/logs/com_whmcsbridge.api.log.php
```

## Enable Authentication Plugin

After installation, enable the WHMCS authentication plugin:

1. Go to **System > Plugins**
2. Search for "WHMCS"
3. Enable **Authentication - WHMCS**
4. (Optional) Reorder authentication plugins if needed

Now users can log in with their WHMCS email and password.

## Group Mappings

Map WHMCS products to Joomla usergroups:

1. Go to **Components > WHMCS Bridge > Group Mappings**
2. You'll see all WHMCS products listed
3. Select the Joomla usergroups to assign for each product
4. Click **Apply** to save all mappings

When users sync, they're automatically added to groups based on their active products.

## Syncing Users

### Manual Sync
- **Dashboard > Sync All Users** - Sync all WHMCS clients to Joomla
- **Users view > Sync button** - Sync individual users

### Automatic Sync
- Enable **Sync on Login** to update user data when they log in

## Architecture Notes

- Users are matched by **email address** (case-insensitive)
- Sync is **one-way**: WHMCS → Joomla
- WHMCS is the **source of truth** for user credentials
- Joomla passwords are set to a random value (login happens via WHMCS API)
