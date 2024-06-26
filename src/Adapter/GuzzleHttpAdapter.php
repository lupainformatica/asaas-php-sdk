<?php
namespace Softr\Asaas\Adapter;


// Asaas
use Softr\Asaas\Exception\HttpException;

// GuzzleHttp
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;



/**
 * Guzzle Http Adapter
 *
 * @author Agência Softr <agencia.softr@gmail.com>
 */
class GuzzleHttpAdapter implements AdapterInterface
{
    /**
     * Client Instance
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * Command Response
     *
     * @var Response|GuzzleHttp\Message\ResponseInterface
     */
    protected $response;

    /**
     * Constructor
     *
     * @param  string                $token   Access Token
     * @param  ClientInterface|null  $client  Client Instance
     */
    public function __construct($token, ClientInterface $client = null)
    {
        if(version_compare(ClientInterface::MAJOR_VERSION, '6') === 1)
        {
            $this->client = $client ?: new Client(['headers' => ['access_token' => $token]]);
        }
        else
        {
            $this->client = $client ?: new Client();

            $this->client->setDefaultOption('headers/access_token', $token);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($url)
    {
        try
        {
            $this->response = $this->client->get($url);
        }
        catch(RequestException $e)
        {
            $this->response = $e->getResponse();

            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($url)
    {
        try
        {
            $this->response = $this->client->delete($url);
        }
        catch(RequestException $e)
        {
            $this->response = $e->getResponse();

            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function put($url, $content = '')
    {
        $options = [];
        $options['body'] = $content;

        try
        {
            $this->response = $this->client->put($url, $options);
        }
        catch(RequestException $e)
        {
            $this->response = $e->getResponse();

            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function post($url, $content = '')
    {
        $options = [];
        $options['json'] = $content;

        try
        {
            $this->response = $this->client->post($url, $options);
        }
        catch(RequestException $e)
        {
            $this->response = $e->getResponse();

            $this->handleError();
        }

        return $this->response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestResponseHeaders()
    {
        if(null === $this->response)
        {
            return;
        }

        return [
            'reset'     => (int) (string) $this->response->getHeader('RateLimit-Reset'),
            'remaining' => (int) (string) $this->response->getHeader('RateLimit-Remaining'),
            'limit'     => (int) (string) $this->response->getHeader('RateLimit-Limit'),
        ];
    }

    /**
     * @throws HttpException
     */
    protected function handleError()
    {
        $body = (string) $this->response->getBody();
        $code = (int) $this->response->getStatusCode();

        $content = json_decode($body);
		
		$errors = [];
		if (isset($content->errors)) {
			foreach ((array)$content->errors as $error) {
				$errors[] = $error->code . ': ' . $error->description;
			}
		}

		if (!empty($errors)) {
			throw new HttpException(implode('<br>', $errors), $code);
		}

        throw new HttpException(isset($content->message) ? $content->message : 'Request not processed.', $code);
    }
}
