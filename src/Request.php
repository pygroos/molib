<?php
/**
 * 	This file is part of the pygroos/molib package.
 *
 *	(c)	pygroos <pygroos@gmail.com>
 * 
 *	For the full copyright and license information, please view the LICENSE
 * 	file that was distributed with this source code. 
 */

namespace Pygroos\Molib;

class Request
{
	const DEFAULT_TIMEOUT  = 10;
	const ERROR_UNKNOWN    = -1;
	const ERROR_SUCCESS	   = 0;
	const ERROR_BASE 	   = 10000;
	const ERROR_PARAMETER 					= self::ERROR_BASE + 1;
	const ERROR_NETWORK_PARAMETER			= self::ERROR_BASE + 2;
	const ERROR_NETWORK_CURL_INIT			= self::ERROR_BASE + 3;
	const ERROR_NETWORK_HTTP_HEADER         = self::ERROR_BASE + 4;
	const ERROR_NETWORK_HTTP_STATUS         = self::ERROR_BASE + 5;
	const ERROR_NETWORK_CURL_NOT_SUPPORTED  = self::ERROR_BASE + 6;

	private $arrMethods	= [ 'GET', 'POST' ];

	public function get( $arrParam, & $arrResponse )
	{
		if ( ! is_array( $arrParam ) || 0 == count( $arrParam ) )
		{
			return self::ERROR_PARAMETER;
		}

		$arrParam['method'] = 'GET';
		$arrResponse = '';

		$this->http( $arrParam, $arrResponse );

		return $arrResponse;
	}

	public function post( $arrParam, & $arrResponse )
	{
		if ( ! is_array( $arrParam ) || 0 == count( $arrParam ) )
		{
			return self::ERROR_PARAMETER;
		}

		$arrParam['method'] = 'POST';
		$arrResponse = '';

		$this->http( $arrParam, $arrResponse );

		return $arrResponse;
	}

	public function http( $arrParam, & $arrResponse )
	{
		$sMethod  = $this->_isValidMethod( $arrParam ) ? $arrParam['method'] : $this->_getDefaultMethod();
		$arrData  = isset( $arrParam['data'] ) ? $arrParam['data'] : [];
		$sUrl     = isset( $arrParam['url'] ) ? $arrParam['url'] : '';
		$nTimeout = isset( $arrParam['timeout'] ) ? $arrParam['timeout'] : self::DEFAULT_TIMEOUT;
		$arrRequest = [
			'url'		=> $sUrl,
			'method'	=> $sMethod,
			'data'		=> $arrData
		];
		$nHttpCode = 0;
		$sResponse = '';
		$nRet = $this->_sendHttpRequest( $arrRequest, $nTimeout, $nHttpCode, $sResponse );

		if ( self::ERROR_SUCCESS == $nRet )
		{
			$arrResponse = [
				'data'   => $sResponse,
				'status' => $nHttpCode
			];
		}

		return $nRet;
	}

	private function _sendHttpRequest( $arrRequest, $nTimeout, & $nHttpCode = 0, & $sResponseBody = null )
	{
		if ( ! function_exists( 'curl_init' ) )
		{
			return self::ERROR_NETWORK_CURL_NOT_SUPPORTED;
		}
		if ( ! is_numeric( $nTimeout ) )
		{
			return self::ERROR_PARAMETER;
		}
		if ( ! Helper::isExistString( $arrRequest['url'] ) )
		{
			return self::ERROR_PARAMETER;
		}
		if ( count( $arrRequest['data'] ) == 0 )
		{
			return self::ERROR_PARAMETER;
		}

		$nRet = self::ERROR_UNKNOWN;
		$oCurl = curl_init();

		if ( $this->_isValidCUrlHandle( $oCurl ) )
		{
			if ( false !== stripos( $arrRequest['url'], "https://" ) )
			{
				//
				//	set options for https request
				//
				//	FALSE to stop cURL from verifying the peer's certificate.
				curl_setopt( $oCurl, CURLOPT_SSL_VERIFYPEER, false );
				//
				//	1	- to check the existence of a common name in the SSL peer certificate.
				//	2	- to check the existence of a common name and also verify that
				//		  it matches the hostname provided.
				//	In production environments the value of this option
				//	should be kept at 2 (default value).
				//
				curl_setopt( $oCurl, CURLOPT_SSL_VERIFYHOST, 2 );
			}
			curl_setopt( $oCurl, CURLOPT_CONNECTTIMEOUT, $nTimeout );
			curl_setopt( $oCurl, CURLOPT_TIMEOUT, $nTimeout );
			curl_setopt( $oCurl, CURLOPT_HEADER, true );
			curl_setopt( $oCurl, CURLOPT_RETURNTRANSFER, true );
			//	return html body while HTTP Status 500
			curl_setopt( $oCurl, CURLOPT_FAILONERROR, false );
			curl_setopt( $oCurl, CURLOPT_HTTP200ALIASES, [ 500 ] );

			if ( 0 == strcasecmp( 'GET', $arrRequest['method'] ) )
			{
				$sDataString = http_build_query( $arrRequest['data'], '', '&', PHP_QUERY_RFC3986 );
				$arrRequest['url'] .= sprintf( "%s%s", ( strchr( $arrRequest['url'], '?' ) ? '&' : '?' ), $sDataString );
				curl_setopt( $oCurl, CURLOPT_CUSTOMREQUEST, 'GET' );
				curl_setopt( $oCurl, CURLOPT_URL, $arrRequest['url'] );
			}
			elseif ( 0 == strcasecmp( 'POST', $arrRequest['method'] ) ) 
			{
				curl_setopt( $oCurl, CURLOPT_POST, true );
				curl_setopt( $oCurl, CURLOPT_POSTFIELDS, $arrRequest['data'] );
				curl_setopt( $oCurl, CURLOPT_URL, $arrRequest['url'] );
			}
			//
			//	send request and set return buffer
			//
			$sResponse		= curl_exec( $oCurl );
			$sResponseBody	= '';
			//	...
			$nHttpCode	 = curl_getinfo( $oCurl, CURLINFO_HTTP_CODE );
			$nHeaderSize = curl_getinfo( $oCurl, CURLINFO_HEADER_SIZE );
			//	close curl
			curl_close( $oCurl );
			$oCurl = null;
			
			if ( $nHeaderSize > 0 )
			{
				if ( 200 == $nHttpCode )
				{
					//	successfully
					$nRet = self::ERROR_SUCCESS;
					$sResponseBody = substr( $sResponse, $nHeaderSize );
				}
				else
				{
					$nRet = self::ERROR_NETWORK_HTTP_STATUS;
				}
			}
			else
			{
				//	error in http header
				$nRet = self::ERROR_NETWORK_HTTP_HEADER;
			}
		}
		else
		{
			$nRet = self::ERROR_NETWORK_CURL_INIT;
		}

		return $nRet;
	}

	private function _isValidMethod( $arrParam )
	{
		if ( ! isset( $arrParam['method'] ) || empty( $arrParam['method'] ) )
		{
			return false;
		}

		$sMethod = strtoupper( $arrParam['method'] );

		return in_array( $sMethod, $this->arrMethods );
	}

	private function _getDefaultMethod()
	{
		return $this->arrMethods[0];
	}
	
	private function _isValidCUrlHandle( $oCUrl )
	{
		return ( isset( $oCUrl ) && false !== $oCUrl && is_resource( $oCUrl ) );
	}
}