<?php
namespace Craft;

/**
 *
 */
class ElliottEndpoints
{
	const Ping                 = '@@@elliottEndpointUrl@@@actions/elliott/app/ping';
	const CheckForUpdates      = '@@@elliottEndpointUrl@@@actions/elliott/app/checkForUpdates';
	const DownloadUpdate       = '@@@elliottEndpointUrl@@@actions/elliott/app/downloadUpdate';
	const TransferLicense      = '@@@elliottEndpointUrl@@@actions/elliott/app/transferLicenseToCurrentDomain';
	const GetPackageInfo       = '@@@elliottEndpointUrl@@@actions/elliott/app/getPackageInfo';
	const PurchasePackage      = '@@@elliottEndpointUrl@@@actions/elliott/app/purchasePackage';
}
