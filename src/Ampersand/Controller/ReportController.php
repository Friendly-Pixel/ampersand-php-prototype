<?php

namespace Ampersand\Controller;

use Ampersand\IO\RDFGraph;
use Ampersand\Misc\Reporter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ReportController extends AbstractController
{
    protected function guard(): void
    {
        $this->preventProductionMode();
        $this->requireAdminRole();
    }

    public function exportMetaModel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        // Content negotiation
        $acceptHeader = $request->getQueryParams()['format'] ?? $request->getHeaderLine('Accept');
        $rdfFormat = RDFGraph::getResponseFormat($acceptHeader);

        $graph = new RDFGraph($this->app->getModel(), $this->app->getSettings());

        // Output
        $mimetype = $rdfFormat->getDefaultMimeType();
        switch ($mimetype) {
            case 'text/html':
                $response->getBody()->write($graph->dump('html'));
                return $response->withHeader('Content-Type', 'text/html');
            case 'text/plain':
                $response->getBody()->write($graph->dump('text'));
                return $response->withHeader('Content-Type', 'text/plain');
            default:
                $filename = $this->app->getName() . "_meta-model_" . date('Y-m-d\TH-i-s') . "." . $rdfFormat->getDefaultExtension();
                $response->getBody()->write($graph->serialise($rdfFormat));
                return $response
                    ->withHeader('Content-Type', $rdfFormat->getDefaultMimeType())
                    ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }
    }

    public function reportRelations(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        // Get report
        $reporter = new Reporter(new JsonEncoder(), $response->getBody());
        $reporter->reportRelationDefinitions($this->app->getModel()->getRelations(), 'json');

        // Return reponse
        return $response->withHeader('Content-Type', 'application/json;charset=utf-8');
    }

    public function conjunctUsage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        // Get report
        $reporter = new Reporter(new JsonEncoder(), $response->getBody());
        $reporter->reportConjunctUsage($this->app->getModel()->getAllConjuncts(), 'json');

        // Return reponse
        return $response->withHeader('Content-Type', 'application/json;charset=utf-8');
    }

    public function conjunctPerformance(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        // Get report
        $reporter = new Reporter(new CsvEncoder(';', '"'), $response->getBody());
        $reporter->reportConjunctPerformance($this->app->getModel()->getAllConjuncts(), 'csv');
        
        // Set response headers
        $filename = $this->app->getName() . "_conjunct-performance_" . date('Y-m-d\TH-i-s') . ".csv";
        return $response->withHeader('Content-Disposition', "attachment; filename={$filename}")
                        ->withHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function interfaces(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();

        // Input
        $details = $request->getQueryParams()['details'] ?? false;

        // Get report
        $reporter = new Reporter(new CsvEncoder(';', '"'), $response->getBody());
        if ($details) {
            $reporter->reportInterfaceObjectDefinitions($this->app->getModel()->getAllInterfaces(), 'csv');
        } else {
            $reporter->reportInterfaceDefinitions($this->app->getModel()->getAllInterfaces(), 'csv');
        }

        // Set response headers
        $filename = $this->app->getName() . "_interface-definitions_" . date('Y-m-d\TH-i-s') . ".csv";
        return $response->withHeader('Content-Disposition', "attachment; filename={$filename}")
                        ->withHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function interfaceIssues(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->guard();
        
        // Get report
        $reporter = new Reporter(new CsvEncoder(';', '"'), $response->getBody());
        $reporter->reportInterfaceIssues($this->app->getModel()->getAllInterfaces(), 'csv');

        // Set response headers
        $filename = $this->app->getName() . "_interface-issues_" . date('Y-m-d\TH-i-s') . ".csv";
        return $response->withHeader('Content-Disposition', "attachment; filename={$filename}")
                        ->withHeader('Content-Type', 'text/csv; charset=utf-8');
    }
}